import os
import re
import socket
import time

class AccessProReader:
    def __init__(self, ip, port=100, timeout=5.0):
        self.ip = ip
        self.port = port
        self.timeout = timeout
        self.sock = None

    # --- MÉTODOS DE CONEXIÓN ---
    def conectar(self):
        try:
            self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            self.sock.settimeout(self.timeout)
            self.sock.connect((self.ip, self.port))
            return True
        except Exception as e:
            print(f"[-] Error al conectar a {self.ip}: {e}")
            return False

    def desconectar(self):
        if self.sock:
            self.sock.close()
            print("[*] Conexión cerrada.")

    # --- UTILIDADES INTERNAS ---
    def _calcular_checksum(self, trama_bytes):
        suma = sum(trama_bytes)
        cc = (~(suma & 0xFF) & 0xFF) + 1
        return cc & 0xFF

    def _enviar_comando(self, cmd_bytes):
        """Encapsula el envío y recepción básica."""
        try:
            self.sock.send(bytearray(cmd_bytes))
            return self.sock.recv(1024)
        except Exception as e:
            print(f"[-] Error de comunicación: {e}")
            return None

    def _extraer_niv_limpio(self, raw_bytes):
        """Lógica de limpieza de NIV (17 caracteres)."""
        try:
            texto = raw_bytes.decode('ascii', errors='ignore')
            limpio = "".join([c for c in texto if c.isalnum()])
            if len(limpio) >= 17:
                return limpio[:17].upper()
            return limpio if limpio else "FORMATO_INVALIDO"
        except:
            return "HEX:" + raw_bytes.hex()[:10]

    def _armar_leer_banco(self, bank, addr, cnt):
        trama = [0x0A, 0xFF, 0x09, 0x88, 0x00, 0x00, 0x00, 0x00, bank, addr, cnt]
        trama.append(self._calcular_checksum(trama))
        return trama

    # --- MÉTODOS DE CONSULTA (GETTERS) ---
    def get_firmware(self):
        resp = self._enviar_comando([0x0A, 0xFF, 0x02, 0x22, 0xD3])
        if resp and len(resp) >= 6:
            return f"V{resp[4]}.{resp[5]}"
        return "Desconocido"

    def get_ip_settings(self):
        """Consulta configuración de red ajustada a la respuesta de 19 bytes."""
        self.sock.send(bytearray([0x0A, 0xFF, 0x02, 0x2B, 0xCA]))
        
        resp = b""
        intentos = 0
        # Esperamos 19 bytes según tu salida ERR_LEN_19
        while len(resp) < 19 and intentos < 5:
            try:
                chunk = self.sock.recv(1024)
                if not chunk: break
                resp += chunk
            except: break
            intentos += 1
            time.sleep(0.05)

        if resp:
            print(f"[*] RESPUESTA CRUDA (HEX): {resp.hex().upper()}")
            print(f"[*] LONGITUD: {len(resp)} bytes")

        if resp and len(resp) >= 19:
            # El puerto suele estar en los bytes 16 y 17
            puerto = (resp[16] << 8) | resp[17]
            return {
                "ip": ".".join(map(str, resp[4:8])),
                "mask": ".".join(map(str, resp[8:12])),
                "gw": ".".join(map(str, resp[12:16])),
                "port": puerto,
                "mac": "NO_DISPONIBLE_EN_0x2B"
            }
        return None

    def limpiar_buffer(self):
        """Comando 0x44: Clear ID Buffer"""
        resp = self._enviar_comando([0x0A, 0xFF, 0x02, 0x44, 0xB1])
        return True if (resp and len(resp) >= 4 and resp[3] == 0x00) else False

    def obtener_conteo_buffer(self):
        """Comando 0x43: Query ID Count"""
        resp = self._enviar_comando([0x0A, 0xFF, 0x02, 0x43, 0xB2])
        if resp and len(resp) >= 7 and resp[3] == 0x00:
            return (resp[4] << 8) | resp[5]
        return 0

    def get_rf_settings(self):
        """Consulta potencia y antenas activas con limpieza de buffer."""
        # Flush buffer
        self.sock.setblocking(False)
        try:
            while self.sock.recv(1024): pass
        except: pass
        finally: self.sock.setblocking(True)

        # Consultar Potencia (0x26) y Antenas (0x2A)
        t_p = [0x0A, 0xFF, 0x02, 0x26]; t_p.append(self._calcular_checksum(t_p))
        resp_p = self._enviar_comando(t_p)
        time.sleep(0.1)
        t_a = [0x0A, 0xFF, 0x02, 0x2A]; t_a.append(self._calcular_checksum(t_a))
        resp_a = self._enviar_comando(t_a)

        if resp_p and len(resp_p) >= 5 and resp_a and len(resp_a) >= 5:
            mask = resp_a[4]
            return {
                "potencia": resp_p[4],
                "antenas": [i+1 for i in range(4) if (mask & (1 << i))],
                "mask_hex": hex(mask).upper()
            }
        return None

    # --- MÉTODOS DE CONFIGURACIÓN (SETTERS) ---
    def set_rf_power(self, potencia):
        """Ajusta potencia (0-30 dBm) en las 4 antenas."""
        if not (0 <= potencia <= 30): return False
        trama = [0x0A, 0xFF, 0x06, 0x25, potencia, potencia, potencia, potencia]
        trama.append(self._calcular_checksum(trama))
        resp = self._enviar_comando(trama)
        return True if (resp and len(resp) >= 4 and resp[3] == 0x00) else False

    def set_antennas(self, mask):
        """Configura máscara de antenas activas."""
        if mask == 0: return False
        trama = [0x0A, 0xFF, 0x03, 0x29, mask]
        trama.append(self._calcular_checksum(trama))
        resp = self._enviar_comando(trama)
        return True if (resp and len(resp) >= 4 and resp[3] == 0x00) else False

    def set_ip_config(self, new_ip, new_mask, new_gw, new_port):
        """Configura nueva red y solicita reset."""
        try:
            data = [int(x) for x in new_ip.split('.')] + \
                   [int(x) for x in new_mask.split('.')] + \
                   [int(x) for x in new_gw.split('.')]
            data.append((new_port >> 8) & 0xFF)
            data.append(new_port & 0xFF)
            
            trama = [0x0A, 0xFF, len(data) + 4, 0x2C] + data
            trama.append(self._calcular_checksum(trama))
            
            resp = self._enviar_comando(trama)
            if resp and len(resp) >= 4 and resp[3] == 0x00:
                print("[+] IP cambiada. Reiniciando...")
                self.reset_hard()
                return True
        except: return False
        return False

    # --- MÉTODOS DE OPERACIÓN ---
    def leer_tag_completo(self):
        """Lee TID, EPC y USER."""

        # 1. Leer TID (Banco 0x02)
        r_tid = self._enviar_comando(
            self._armar_leer_banco(0x02, 0x00, 0x06)
        )

        if not r_tid or r_tid[3] != 0x00:
            return None

        # 2. Leer EPC (Banco 0x01)
        r_epc = self._enviar_comando(
            self._armar_leer_banco(0x01, 0x02, 0x06)
        )

        # 3. Leer USER (Banco 0x03)
        r_user = self._enviar_comando(
            self._armar_leer_banco(0x03, 0x00, 0x0C)
        )

        # DATOS
        tid_hex = r_tid[5:-1].hex().upper() if r_tid else ""
        epc_hex = r_epc[5:-1].hex().upper() if r_epc else ""
        user_hex = r_user[15:].hex().upper() if r_user else ""

        # ASCII
        tid_ascii = r_tid[5:-1].decode('ascii', errors='ignore') if r_tid else ""
        epc_ascii = r_epc[5:-1].decode('ascii', errors='ignore') if r_epc else ""
        user_ascii = r_user[5:-1].decode('ascii', errors='ignore') if r_user else ""

        print("\nTID:", tid_hex, "TID ASCII:", tid_ascii)
        print("EPC:", epc_hex, "EPC ASCII:", epc_ascii)
        print("USER:", user_hex, "NIV:", user_ascii)
        print("-" * 40)

        return {
            "tid": tid_hex if r_tid and r_tid[3] == 0x00 else "SIN DATOS",
            "epc": epc_hex if r_epc and r_epc[3] == 0x00 else "SIN DATOS",
            "user": user_hex if r_user and r_user[3] == 0x00 else "SIN DATOS"
        }
    
    def reset_hard(self):
        """Envía comando de Reset (0x21)."""
        return self._enviar_comando([0x0A, 0xFF, 0x02, 0x21, 0xD4])

    def safe_reset(self):
        """Valida IP antes de resetear"""
        settings = self.get_ip_settings()
        if settings and settings['ip'] == self.ip:
            return self.reset_hard()
        return False