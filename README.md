### Overview

This repository contains production-level WooCommerce customizations and backend integrations used in a live e-commerce website.

Sensitive information such as webhook endpoints, API URLs, access tokens, and store-specific operational data have been intentionally removed or abstracted to ensure security and reusability.

The project includes a Flask-based backend service deployed on Google Cloud, which integrates WooCommerce with the WhatsApp Cloud API and provides a fully functional chatbot infrastructure.

### Key Features
  •	WooCommerce Customizations
	•	Production-ready custom logic used in a live store
	•	Event-driven order handling and webhook integrations
	•	Clean separation of business logic and configuration
	•	Flask Backend (Google Cloud)
	•	Webhook-based architecture for receiving WooCommerce and WhatsApp events
	•	Modular and extensible Flask application structure
	•	WhatsApp Cloud API Integration
	•	Real-time WhatsApp notifications for new WooCommerce orders
	•	Automated customer messaging via webhook-triggered flows
	•	Chatbot System
	•	Incoming customer messages processed through a Flask webhook
	•	Supports automated responses and trigger-based actions
	•	Designed for future AI model integration (e.g., Gemini)

### Architecture Highlights
  •	Secure webhook handling and payload parsing
	•	Event-driven communication between WooCommerce and WhatsApp
	•	Cloud-ready deployment with scalability in mind
	•	AI-ready design for conversational automation and intelligent responses

This repository demonstrates real-world e-commerce automation, backend integration, and cloud-based deployment practices suitable for production environments.
