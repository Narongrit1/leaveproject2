import json
import sys
from io import BytesIO
from pathlib import Path

import fitz
from PIL import Image, ImageDraw, ImageFont


def fit_text(text, limit):
    text = " ".join(str(text or "").split())
    return text if len(text) <= limit else text[: max(1, limit - 1)] + "…"


def put_box(page, x0, y0, x1, y1, text, size=11, align="left", fontfile=None):
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

    if align == "center":
        x = x0 + ((x1 - x0) - display_width) / 2
    else:
        x = x0 + 2
    y = y0 + ((y1 - y0) - display_height) / 2

    stream = BytesIO()
    image.save(stream, format="PNG")
    page.insert_image(fitz.Rect(x, y, x + display_width, y + display_height), stream=stream.getvalue(), overlay=True)


def draw_check(page, x, y, size=13):
    page.draw_line(fitz.Point(x + 2, y + size * 0.55), fitz.Point(x + size * 0.42, y + size - 2), color=(0, 0, 0), width=1.2, overlay=True)
    page.draw_line(fitz.Point(x + size * 0.42, y + size - 2), fitz.Point(x + size - 2, y + 2), color=(0, 0, 0), width=1.2, overlay=True)


def fill_copy(page, data, offset, fontfile):
    put_box(page, 148, 60 + offset, 280, 78 + offset, data.get("personnel_code", "-"), 11, "center", fontfile)
    put_box(page, 378, 60 + offset, 482, 78 + offset, data.get("submitted_date", "-"), 11, "center", fontfile)
    put_box(page, 148, 82 + offset, 280, 100 + offset, data.get("full_name", "-"), 11, "center", fontfile)
    put_box(page, 378, 82 + offset, 515, 100 + offset, data.get("department_name", "-"), 10, "center", fontfile)

    if data.get("request_type") == "workday_swap":
        put_box(page, 150, 199 + offset, 275, 216 + offset, data.get("absent_date", "-"), 11, "center", fontfile)
        put_box(page, 416, 199 + offset, 590, 216 + offset, data.get("makeup_date", "-"), 11, "center", fontfile)
        put_box(page, 120, 221 + offset, 590, 238 + offset, fit_text(data.get("reason", ""), 90), 10, "left", fontfile)
    else:
        put_box(page, 210, 100.25 + offset, 282, 117.25 + offset, data.get("work_date", "-"), 11, "center", fontfile)
        reason = data.get("reason_type") or "other"
        check_y = {
            "forgot_check_in": 121,
            "forgot_check_out": 140,
            "scanner_error": 156,
            "other": 175,
        }.get(reason, 190)
        draw_check(page, 37, check_y + offset)
        if reason == "other":
            put_box(page, 150, 169 + offset, 568, 187 + offset, fit_text(data.get("other_reason") or data.get("reason", ""), 85), 10, "left", fontfile)


def main():
    if len(sys.argv) != 4:
        raise SystemExit("usage: render_attendance_certificate_pdf.py input.json template.pdf output.pdf")

    data = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
    template_path = Path(sys.argv[2])
    output_path = Path(sys.argv[3])
    font_path = Path(r"C:\Windows\Fonts\angsana.ttc")
    if not font_path.exists():
        font_path = Path(r"C:\Windows\Fonts\tahoma.ttf")
    fontfile = str(font_path)

    doc = fitz.open(str(template_path))
    page = doc[0]
    fill_copy(page, data, 0, fontfile)
    page.set_cropbox(fitz.Rect(0, 0, 590, 390))
    doc.save(str(output_path), garbage=4, deflate=True)
    doc.close()


if __name__ == "__main__":
    main()
