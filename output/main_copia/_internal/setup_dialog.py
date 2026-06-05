import customtkinter as ctk
from tkinter import messagebox

class SetupDialog(ctk.CTkToplevel):
    def __init__(self, parent, callback):
        super().__init__(parent)
        self.callback = callback
        self.title("Configuración Inicial")
        self.geometry("350x480")
        self.resizable(False, False)
        self.transient(parent)
        self.grab_set()

        ctk.CTkLabel(self, text="CONFIGURACIÓN DE ESTACIÓN", font=ctk.CTkFont(size=14, weight="bold")).pack(pady=20)

        # Campos vacíos
        ctk.CTkLabel(self, text="Dirección IP:").pack(pady=(10, 0))
        self.ent_ip = ctk.CTkEntry(self, width=200)
        self.ent_ip.pack(pady=5)

        ctk.CTkLabel(self, text="Nombre del Lector:").pack(pady=(10, 0))
        self.ent_name = ctk.CTkEntry(self, width=200)
        self.ent_name.pack(pady=5)

        ctk.CTkLabel(self, text="MAC Address:").pack(pady=(10, 0))
        self.ent_mac = ctk.CTkEntry(self, width=200)
        self.ent_mac.pack(pady=5)
        
        ctk.CTkLabel(self, text="Antena:").pack(pady=(10,0))
        self.ent_antena = ctk.CTkEntry(self, width=200)
        self.ent_antena.pack(pady=5)

        self.btn = ctk.CTkButton(self, text="Confirmar e Iniciar", fg_color="#2ecc71", command=self.confirmar)
        self.btn.pack(pady=30)

    def confirmar(self):
        ip = self.ent_ip.get().strip()
        name = self.ent_name.get().strip()
        mac = self.ent_mac.get().strip()
        antena = self.ent_antena.get().strip()

        if ip and name and mac and antena:
            self.callback(ip, name, mac, antena)
            self.destroy()
        else:
            messagebox.showwarning("Atención", "Todos los campos son obligatorios")