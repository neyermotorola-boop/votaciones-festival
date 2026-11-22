"""CP-09: N solicitudes simultaneas del mismo usuario a la misma categoria."""
import re
import sys
import threading
import urllib.parse
import urllib.request
import http.cookiejar

BASE = "http://localhost:8080"
VOTACION = int(sys.argv[1])          # id de la votacion
EMAIL = sys.argv[2]
N = int(sys.argv[3]) if len(sys.argv) > 3 else 10

jar = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))


def token(html):
    return re.search(r'name="_token" value="([^"]+)"', html).group(1)


def post(url, data):
    req = urllib.request.Request(BASE + url, urllib.parse.urlencode(data).encode())
    try:
        r = opener.open(req)
        return r.status, r.read().decode()
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode()


# 1) registro de un usuario nuevo (datos ficticios)
html = opener.open(BASE + "/registro").read().decode()
post("/registro", {"_token": token(html), "name": "Prueba Concurrencia", "email": EMAIL,
                   "password": "ClaveDePrueba123", "password_confirmation": "ClaveDePrueba123", "acepta_datos": "1"})

# 2) token CSRF y primera opcion de la votacion
html = opener.open(f"{BASE}/votaciones/{VOTACION}").read().decode()
csrf = token(html)
opcion = int(re.search(r'name="opcion_id" id="o\d+" value="(\d+)"', html).group(1))

# 3) N solicitudes simultaneas (barrera para que arranquen a la vez)
barrera = threading.Barrier(N)
resultados = []


def disparar():
    barrera.wait()
    status, cuerpo = post(f"/votaciones/{VOTACION}/votar", {"_token": csrf, "opcion_id": opcion})
    ok = "gracias" in cuerpo.lower() or "Voto registrado" in cuerpo
    resultados.append((status, "VOTO REGISTRADO" if ok else "RECHAZADO (ya votaste en la categoria)"))


hilos = [threading.Thread(target=disparar) for _ in range(N)]
[h.start() for h in hilos]
[h.join() for h in hilos]

print(f"Solicitudes simultaneas enviadas: {N}")
for k in sorted(set(r[1] for r in resultados)):
    print(f"  {k}: {sum(1 for r in resultados if r[1] == k)}")
