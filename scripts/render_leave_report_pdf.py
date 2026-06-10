import json
import sys
from io import BytesIO
from pathlib import Path

import fitz
from PIL import Image, ImageDraw, ImageFont


def fit_text(text, limit):
    text = " ".join(str(text or "-").split())
    return text if len(text) <= limit else text[: max(1, limit - 1)] + "…"


def put(page, x, y, text, size=10, align=None, fontfile=None):
    text = str(text or "")
    scale = 3
    font = ImageFont.truetype(fontfile, max(1, int(size * scale)))
    probe = Image.new("RGBA", (1, 1), (255, 255, 255, 0))
    draw = ImageDraw.Draw(probe)
    bbox = draw.textbbox((0, 0), text, font=font)
    width = max(1, bbox[2] - bbox[0] + 6)
    height = max(1, bbox[3] - bbox[1] + 6)
    image = Image.new("RGBA", (width, height), (255, 255, 255, 0))
    draw = ImageDraw.Draw(image)
    draw.text((3 - bbox[0], 3 - bbox[1]), text, font=font, fill=(0, 0, 0, 255))

    display_width = width / scale
    display_height = height / scale
    if align == "center":
        x -= display_width / 2

    stream = BytesIO()
    image.save(stream, format="PNG")
    rect = fitz.Rect(x, y - display_height, x + display_width, y)
    page.insert_image(rect, stream=stream.getvalue(), overlay=True)


def put_box(page, x0, y0, x1, y1, text, size=8, align="center", fontfile=None):
    text = str(text or "")
    if text == "":
        return
    scale = 3
    font = ImageFont.truetype(fontfile, max(1, int(size * scale)))
    probe = Image.new("RGBA", (1, 1), (255, 255, 255, 0))
    draw = ImageDraw.Draw(probe)
    bbox = draw.textbbox((0, 0), text, font=font)
    width = max(1, bbox[2] - bbox[0] + 6)
    height = max(1, bbox[3] - bbox[1] + 6)
    image = Image.new("RGBA", (width, height), (255, 255, 255, 0))
    draw = ImageDraw.Draw(image)
    draw.text((3 - bbox[0], 3 - bbox[1]), text, font=font, fill=(0, 0, 0, 255))
    display_width = width / scale
    display_height = height / scale

    if align == "left":
        x = x0 + 2
    else:
        x = x0 + ((x1 - x0) - display_width) / 2
    y = y0 + ((y1 - y0) - display_height) / 2

    stream = BytesIO()
    image.save(stream, format="PNG")
    page.insert_image(fitz.Rect(x, y, x + display_width, y + display_height), stream=stream.getvalue(), overlay=True)


def erase(page, x0, y0, x1, y1):
    bg = (1.0, 0.992, 0.86)
    page.draw_rect(fitz.Rect(x0, y0, x1, y1), color=bg, fill=bg, overlay=True)


def underline(page, x0, y, x1):
    page.draw_line(fitz.Point(x0, y), fitz.Point(x1, y), color=(0.15, 0.13, 0.12), width=0.45, overlay=True)


def main():
    if len(sys.argv) != 4:
        raise SystemExit("usage: render_leave_report_pdf.py input.json template.pdf output.pdf")

    data_path = Path(sys.argv[1])
    template_path = Path(sys.argv[2])
    output_path = Path(sys.argv[3])
    data = json.loads(data_path.read_text(encoding="utf-8"))

    font_path = Path(r"C:\Windows\Fonts\angsana.ttc")
    if not font_path.exists():
        font_path = Path(r"C:\Windows\Fonts\tahoma.ttf")
    fontfile = str(font_path)

    doc = fitz.open(str(template_path))
    page = doc[0]
    header_line4_text_shift = 1.5 * 72 / 25.4
    start_work_label_shift = 2 * 72 / 25.4
    start_work_right_trim = 0.5 * 72 / 2.54

    # Header fields. The static labels already exist in the template PDF.
    erase(page, 116, 68, 300, 88)
    underline(page, 116, 87, 300)
    put(page, 120, 82, data.get("full_name", "-"), 11, fontfile=fontfile)

    erase(page, 470, 68, 580, 88)
    underline(page, 470, 87, 580)
    put(page, 476, 82, data.get("position_name", "-"), 11, fontfile=fontfile)

    erase(page, 675, 68, 827, 88)
    underline(page, 675, 87, 827)
    put(page, 680, 82, data.get("department_name", "-"), 9, fontfile=fontfile)

    erase(page, 126, 98, 210, 118)
    underline(page, 126, 117 - header_line4_text_shift, 210)
    put_box(page, 126, 98 - header_line4_text_shift, 210, 118 - header_line4_text_shift, data.get("personnel_code", "-"), 11, align="center", fontfile=fontfile)

    erase(page, 206, 88, 310, 106)
    put(page, 206 + start_work_label_shift, 102, "วันเริ่มงาน(Start Date)", 11, fontfile=fontfile)

    erase(page, 286, 106, 436, 118)
    erase(page, 300, 98, 436, 118)
    underline(page, 310, 117 - header_line4_text_shift, 436 - start_work_right_trim)
    put_box(page, 310, 98 - header_line4_text_shift, 436 - start_work_right_trim, 118 - header_line4_text_shift, data.get("start_work_date", "-"), 11, align="center", fontfile=fontfile)

    erase(page, 424, 88, 604, 106)
    put(page, 442, 102, "สิทธิการลา (Leave Entitlement)", 11, fontfile=fontfile)

    erase(page, 604, 98, 626, 118)
    put_box(page, 604, 98 - header_line4_text_shift, 626, 118 - header_line4_text_shift, data.get("personal_entitlement", "-"), 11, align="center", fontfile=fontfile)

    erase(page, 744, 98, 766, 118)
    put_box(page, 744, 98 - header_line4_text_shift, 766, 118 - header_line4_text_shift, data.get("vacation_entitlement", "-"), 11, align="center", fontfile=fontfile)

    # Main table row positions from the reference PDF.
    row_top = 221
    row_height = 15.8
    max_rows = 24
    columns = [
        ("submitted_date", 12, 57, 8, "center", 18),
        ("leave_date", 58, 106, 7, "center", 24),
        ("sick", 108, 139, 9, "center", 8),
        ("personal", 140, 171, 9, "center", 8),
        ("vacation", 172, 203, 9, "center", 8),
        ("training", 204, 244, 9, "center", 8),
        ("offsite", 245, 284, 9, "center", 8),
        ("reason", 286, 426, 8, "left", 18),
        ("requester", 427, 485, 8, "center", 0),
        ("supervisor", 486, 553, 8, "center", 0),
        ("final_approve", 554, 620, 8, "center", 0),
        ("sum_sick", 622, 653, 9, "center", 8),
        ("sum_personal", 654, 685, 9, "center", 8),
        ("sum_vacation", 686, 718, 9, "center", 8),
        ("sum_training", 719, 762, 9, "center", 8),
        ("sum_offsite", 763, 804, 9, "center", 8),
        ("hr", 805, 826, 8, "center", 0),
    ]

    for index, row in enumerate(data.get("rows", [])[:max_rows]):
        y0 = row_top + (index * row_height)
        y1 = y0 + row_height
        for key, x1, x2, size, align, limit in columns:
            value = row.get(key, "")
            if limit > 0:
                value = fit_text(value, limit)
            put_box(page, x1, y0, x2, y1, value, size, align=align, fontfile=fontfile)

    doc.save(str(output_path), garbage=4, deflate=True)
    doc.close()


if __name__ == "__main__":
    main()
