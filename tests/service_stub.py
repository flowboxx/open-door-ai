#!/usr/bin/env python3
"""Stand-in for the flowboxx inquiry service, only for tests/run_wp_test.sh. Usage: service_stub.py PORT STATEFILE LOGFILE"""
import json
import sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

PORT, STATE, LOG = int(sys.argv[1]), sys.argv[2], sys.argv[3]


class H(BaseHTTPRequestHandler):
    def _send(self, code, obj):
        b = json.dumps(obj).encode()
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(b)))
        self.end_headers()
        self.wfile.write(b)

    def log_message(self, *a):
        pass

    def do_GET(self):
        if self.path.endswith("/status") and self.headers.get("Authorization") == "Bearer od_teststub":
            self._send(200, {"abo": open(STATE).read().strip(), "mail_bestaetigt": True})
        else:
            self._send(401, {"fehler": "Schluessel unbekannt."})

    def do_POST(self):
        body = self.rfile.read(int(self.headers.get("Content-Length", 0))).decode()
        if self.path.endswith("/registrieren"):
            open(LOG, "a").write("REG " + body + "\n")
            self._send(201, {"id": "itest", "key": "od_teststub", "mail_gesendet": True})
        elif self.path.endswith("/anfrage") and self.headers.get("Authorization") == "Bearer od_teststub":
            open(LOG, "a").write("ANF " + body + "\n")
            self._send(200, {"ok": True})
        else:
            self._send(401, {"fehler": "Schluessel unbekannt."})


ThreadingHTTPServer(("0.0.0.0", PORT), H).serve_forever()
