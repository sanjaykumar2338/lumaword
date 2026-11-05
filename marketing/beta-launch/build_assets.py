from __future__ import annotations

import base64
import html
from pathlib import Path
from textwrap import wrap

import pymupdf
import qrcode
import qrcode.image.svg

ROOT = Path(__file__).resolve().parents[2]
OUTPUT = Path(__file__).resolve().parent
SOURCE = OUTPUT / "sources"
EXPORT = OUTPUT / "exports"
CHARCOAL = "#171717"
CREAM = "#F5F1E8"
LIME = "#C8F500"
MOSS = "#304600"
LOGIN_URL = "https://lumaword.easytechinfo.net/login"


def logo_data() -> str:
    path = ROOT / "public" / "logo-mark.png"
    return "data:image/png;base64," + base64.b64encode(path.read_bytes()).decode()


def lines(text: str, width: int, size: int, x: int, y: int, color: str, weight: int = 600, leading: float = 1.08) -> str:
    chunks = wrap(text, width=width, break_long_words=False, break_on_hyphens=False)
    spans = "".join(f'<tspan x="{x}" dy="{0 if index == 0 else int(size * leading)}">{html.escape(chunk)}</tspan>' for index, chunk in enumerate(chunks))
    return f'<text x="{x}" y="{y}" fill="{color}" font-family="Arial, Helvetica, sans-serif" font-size="{size}" font-weight="{weight}">{spans}</text>'


def base(width: int, height: int, background: str, eyebrow: str, title: str, body: str, *, footer: str = LOGIN_URL, title_size: int | None = None, screenshot_note: bool = False) -> str:
    pad = int(width * 0.075)
    title_size = title_size or int(width * 0.085)
    foreground = CREAM if background == CHARCOAL else CHARCOAL
    muted = "#CFCBC0" if background == CHARCOAL else "#595750"
    note = ""
    if screenshot_note:
        note_y = int(height * 0.61)
        note = f'<rect x="{pad}" y="{note_y}" width="{width - 2 * pad}" height="{int(height * .18)}" rx="28" fill="none" stroke="{LIME}" stroke-width="4" stroke-dasharray="14 12"/><text x="{width // 2}" y="{note_y + int(height * .09)}" text-anchor="middle" fill="{foreground}" font-family="Arial" font-size="{int(width * .025)}" font-weight="700">APPROVED SCREENSHOT REQUIRED BEFORE PUBLISHING</text>'
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}">
<rect width="{width}" height="{height}" fill="{background}"/>
<path d="M {int(width*.73)} -30 L {width+40} {int(height*.16)} L {width+40} {int(height*.26)} L {int(width*.67)} {int(height*.06)} Z" fill="{LIME}"/>
<image href="{logo_data()}" x="{pad}" y="{pad}" width="{int(width*.095)}" height="{int(width*.095)}" preserveAspectRatio="xMidYMid meet"/>
<text x="{pad + int(width*.115)}" y="{pad + int(width*.062)}" fill="{foreground}" font-family="Arial" font-size="{int(width*.042)}" font-weight="800" letter-spacing="4">LUMA</text>
<text x="{pad}" y="{int(height*.27)}" fill="{LIME if background == CHARCOAL else MOSS}" font-family="Arial" font-size="{int(width*.024)}" font-weight="800" letter-spacing="5">{html.escape(eyebrow.upper())}</text>
{lines(title, 20, title_size, pad, int(height*.37), foreground, 700, .98)}
{lines(body, 43, int(width*.035), pad, int(height*.55), muted, 400, 1.32)}
{note}
<line x1="{pad}" y1="{height-pad-int(width*.04)}" x2="{width-pad}" y2="{height-pad-int(width*.04)}" stroke="{LIME if background == CHARCOAL else CHARCOAL}" stroke-width="3"/>
<text x="{pad}" y="{height-pad}" fill="{foreground}" font-family="Arial" font-size="{int(width*.024)}" font-weight="700">{html.escape(footer)}</text>
</svg>'''


def save(name: str, svg: str) -> None:
    SOURCE.mkdir(parents=True, exist_ok=True)
    EXPORT.mkdir(parents=True, exist_ok=True)
    svg_path = SOURCE / f"{name}.svg"
    png_path = EXPORT / f"{name}.png"
    svg_path.write_text(svg, encoding="utf-8")
    document = pymupdf.open(stream=svg.encode(), filetype="svg")
    document[0].get_pixmap(alpha=False).save(png_path)


def qr_assets() -> None:
    qr = qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_H, box_size=16, border=4)
    qr.add_data(LOGIN_URL)
    qr.make(fit=True)
    qr.make_image(fill_color=CHARCOAL, back_color=CREAM).save(EXPORT / "luma-login-qr.png")
    svg = qr.make_image(image_factory=qrcode.image.svg.SvgPathImage)
    svg.save(SOURCE / "luma-login-qr.svg")


def how_to_join() -> str:
    width, height, pad = 1080, 1350, 80
    steps = [("01", "Visit the LUMA login page"), ("02", "Create a free account"), ("03", "Explore and share feedback")]
    cards = ""
    for index, (number, label) in enumerate(steps):
        y = 455 + index * 190
        cards += f'<rect x="{pad}" y="{y}" width="920" height="150" rx="28" fill="#FFFFFF"/><circle cx="150" cy="{y+75}" r="42" fill="{LIME}"/><text x="150" y="{y+88}" text-anchor="middle" fill="{CHARCOAL}" font-family="Arial" font-size="28" font-weight="800">{number}</text>{lines(label, 30, 34, 225, y+88, CHARCOAL, 700)}'
    qr_data = "data:image/png;base64," + base64.b64encode((EXPORT / "luma-login-qr.png").read_bytes()).decode()
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}"><rect width="1080" height="1350" fill="{CREAM}"/><path d="M800 0H1080V190L760 30Z" fill="{LIME}"/><image href="{logo_data()}" x="80" y="70" width="100" height="100"/><text x="195" y="135" font-family="Arial" font-size="42" font-weight="800" letter-spacing="4">LUMA</text><text x="80" y="285" fill="{MOSS}" font-family="Arial" font-size="25" font-weight="800" letter-spacing="5">DAY 6 · HOW TO JOIN</text>{lines('Join the Free Beta', 18, 86, 80, 390, CHARCOAL, 700, .98)}{cards}<rect x="746" y="1045" width="254" height="254" rx="24" fill="#FFFFFF"/><image href="{qr_data}" x="765" y="1064" width="216" height="216"/><text x="80" y="1160" font-family="Arial" font-size="28" font-weight="700">Scan or visit</text><text x="80" y="1210" font-family="Arial" font-size="24">lumaword.easytechinfo.net/login</text></svg>'''


def flyer() -> str:
    svg = base(1080, 1920, CREAM, "Public beta", "Study Scripture. Take sermons further.", "Daily devotionals, reading plans, sermon-based study, and biblical context in one focused experience.", footer="lumaword.easytechinfo.net/login")
    qr_data = "data:image/png;base64," + base64.b64encode((EXPORT / "luma-login-qr.png").read_bytes()).decode()
    addition = f'<rect x="720" y="1370" width="280" height="280" rx="24" fill="#FFFFFF"/><image href="{qr_data}" x="740" y="1390" width="240" height="240"/><text x="80" y="1510" font-family="Arial" font-size="34" font-weight="700">Scan to join the free beta</text>'
    return svg.replace("</svg>", addition + "</svg>")


def main() -> None:
    SOURCE.mkdir(parents=True, exist_ok=True)
    EXPORT.mkdir(parents=True, exist_ok=True)
    qr_assets()
    sizes = {"feed": (1080, 1350), "square": (1080, 1080), "story": (1080, 1920)}
    for suffix, (width, height) in sizes.items():
        save(f"day-01-beta-announcement-{suffix}", base(width, height, CHARCOAL, "Public beta", "LUMA Beta Is Here", "A Christian study app for devotionals, sermon-based study, and biblical context.", screenshot_note=True))
    carousel = [
        ("Meet LUMA", "A focused place for devotionals, sermon-based study, and biblical context."),
        ("Grow in Scripture", "Read, reflect, pray, and keep returning to God’s Word."),
        ("Make Room for a Daily Devotional", "Begin the day with Scripture, reflection, prayer, and a simple next step."),
        ("Take a Sermon Further", "Keep authorized sermon content, Bible references, notes, and questions together."),
        ("LUMA Is in Beta", "The experience is growing. Your thoughtful feedback can help make it clearer and more useful."),
        ("Be Part of the Beta", "Create a free account. Explore what is live. Help shape what comes next."),
    ]
    for index, (title, body) in enumerate(carousel, 1):
        save(f"day-02-carousel-{index:02d}", base(1080, 1350, CHARCOAL if index % 2 else CREAM, f"Day 2 · {index}/6", title, body))
    save("day-05-grow-in-the-word-story", base(1080, 1920, CREAM, "Day 5 · Grow in the Word", "Make room for Scripture every day.", "A short Story/Reel cover for devotionals, reading plans, and everyday study.", footer="Join the free beta · lumaword.easytechinfo.net/login"))
    save("day-06-how-to-join-feed", how_to_join())
    save("day-07-help-shape-luma-feed", base(1080, 1350, CHARCOAL, "Day 7 · Beta feedback", "Help Shape LUMA", "What felt useful? What was confusing? What would make you come back?", footer="Sign in, then choose Beta feedback"))
    save("feedback-request-template", base(1080, 1350, CREAM, "LUMA beta", "Your feedback matters.", "WHAT FELT USEFUL?  ·  WHAT WAS CONFUSING?  ·  WHAT WOULD MAKE YOU COME BACK?", footer="lumaword.easytechinfo.net/login"))
    save("website-hero", base(1600, 900, CHARCOAL, "Public beta", "LUMA Beta Is Here", "Create a free account. Help shape what comes next.", title_size=120))
    save("email-header", base(1200, 480, CHARCOAL, "Public beta", "LUMA Beta Is Here", "Devotionals · Sermon study · Biblical context", title_size=78))
    save("digital-flyer", flyer())


if __name__ == "__main__":
    main()
