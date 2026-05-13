import socket
try:
    # Mencoba koneksi sederhana ke Google
    sock = socket.create_connection(("google.com", 80))
    print("Koneksi Python normal dan sehat!")
    sock.close()
except Exception as e:
    print(f"Error jaringan Python: {e}")