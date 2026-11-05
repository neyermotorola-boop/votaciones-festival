"""Genera Entrega_Votaciones_Festival.pdf a partir de informe.html.

Uso (desde la carpeta docs/entrega):
    pip install playwright
    python build_pdf.py

Usa Microsoft Edge instalado en el equipo; no descarga navegadores.
"""
from pathlib import Path

from playwright.sync_api import sync_playwright

aqui = Path(__file__).resolve().parent
origen = aqui / "informe.html"
destino = aqui / "Entrega_Votaciones_Festival.pdf"

pie = (
    '<div style="width:100%;font-size:8px;color:#6b6785;padding:0 16mm;'
    'display:flex;justify-content:space-between;font-family:Segoe UI,Arial,sans-serif">'
    "<span>Sistema Web de Votaciones · Festival de la Leyenda Vallenata</span>"
    '<span>Página <span class="pageNumber"></span> de <span class="totalPages"></span></span></div>'
)

with sync_playwright() as p:
    navegador = p.chromium.launch(channel="msedge", headless=True)
    pagina = navegador.new_page()
    pagina.goto(origen.as_uri())
    pagina.wait_for_load_state("networkidle")
    pagina.pdf(
        path=str(destino),
        format="A4",
        print_background=True,
        prefer_css_page_size=True,
        display_header_footer=True,
        header_template="<span></span>",
        footer_template=pie,
        margin={"top": "18mm", "bottom": "20mm", "left": "16mm", "right": "16mm"},
    )
    navegador.close()

print("PDF generado:", destino)
