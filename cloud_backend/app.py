from flask import Flask, request, jsonify
import google.generativeai as genai
import requests
import os

app = Flask(__name__)

# ENV değişkenlerini çek (Cloud Run panelinden girilecek)
VERIFY_TOKEN = os.environ.get("VERIFY_TOKEN")
GEMINI_API_KEY = os.environ.get("GEMINI_API_KEY")
WHATSAPP_TOKEN = os.environ.get("WHATSAPP_TOKEN")
PHONE_NUMBER_ID = os.environ.get("PHONE_NUMBER_ID")

GRAPH_API_URL = f"https://graph.facebook.com/v17.0/{PHONE_NUMBER_ID}/messages"

genai.configure(api_key=GEMINI_API_KEY)
version = 'models/gemini-2.0-flash'
model = genai.GenerativeModel(version)
# ---------------------------------------------------
# WEBHOOK DOĞRULAMA (GET)
# ---------------------------------------------------
@app.route("/webhook", methods=["GET"])
def verify_webhook():
    print("---- GET /webhook isteği geldi ----")
    print("Tüm query parametreleri:", dict(request.args))

    mode = request.args.get("hub.mode")
    token = request.args.get("hub.verify_token")
    challenge = request.args.get("hub.challenge")
    
    print("mode:", mode)
    print("token:", token)
    print("challenge:", challenge)
    print("ENV VERIFY_TOKEN:", VERIFY_TOKEN)

    if mode == "subscribe" and token == VERIFY_TOKEN:
        print("TOKEN EŞLEŞTİ → 200")
        return challenge, 200
    else:
        print("TOKEN EŞLEŞMEDİ → 403")
        return "Forbidden", 403


# ---------------------------------------------------
# MESAJ ALMA (POST)
# ---------------------------------------------------
@app.route("/webhook", methods=["POST"])
def webhook():
    data = request.get_json()
    print("Gelen WhatsApp verisi:", data)

    try:
        message = data["entry"][0]["changes"][0]["value"]["messages"][0]
        sender = message["from"]
        user_text = message["text"]["body"]
        response = model.generate_content(user_text)
        print(f"Kullanıcıdan mesaj geldi: {user_text}")
        
        send_message(sender, response.text)

    except Exception as e:
        print("Mesaj işlenemedi:", e)

    return "EVENT_RECEIVED", 200


# ---------------------------------------------------
# MESAJ GÖNDERME FONKSİYONU 
# ---------------------------------------------------
def send_message(to, message):
    headers = {
        "Authorization": f"Bearer {WHATSAPP_TOKEN}",
        "Content-Type": "application/json"
    }

    data = {
        "messaging_product": "whatsapp",
        "to": to,
        "type": "text",
        "text": {"body": message}
    }

    response = requests.post(GRAPH_API_URL, headers=headers, json=data)
    print("Gönderim Yanıtı:", response.json())


# ---------------------------------------------------
# WOOCOMMERCE SİPARİŞ BİLDİRİM ENDPOINT
# ---------------------------------------------------
@app.route("/order_notification", methods=["POST"])
@app.route("/order_notification/", methods=["POST"])
def order_notification():
    data = request.get_json()

    print("Yeni WooCommerce siparişi:", data)

    try:
        order_id = data["id"]
        total = data["total"]
        customer_phone = data["billing"]["phone"]
        customer_name = data["billing"]["first_name"]

        send_message(
            customer_phone,
            f"Merhaba {customer_name}, sipariş numaranız: {order_id} siparişiniz başarıyla alındı. Teşekkürler"
        )

        return jsonify({"status": "ok"}), 200

    except Exception as e:
        print("Sipariş bildirimi hatası:", e)
        return jsonify({"error": str(e)}), 400


# ---------------------------------------------------
# UYGULAMAYI BAŞLAT (Cloud Run için)
# ---------------------------------------------------
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8080)
