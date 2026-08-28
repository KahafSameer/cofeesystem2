# ☕ Laravel Coffee Shop POS System (Admin Asset Management Feature, Cashier and Chef Role)  
**Author:** Join Coder (www.youtube.com/@joincoder)

This is my second Point of Sale (POS) System built using Laravel 11.
I'm sharing it for free as part of my learning journey in software development.

Feel free to explore, modify, and improve the code!
Since I'm still learning, the structure or best practices might not fully reflect a professional-level project yet.
If you encounter any issues or find areas for improvement, I appreciate your understanding — and contributions are always welcome!
---

## 🛠️ Built With Laravel 11

To run this project, you will need:

- PHP version 8.2.12 or above  
- Composer version 2.6 or above  
- Node.js (for frontend build tools)  
- A code editor, such as Visual Studio Code  
- A web browser  
- XAMPP for Windows or MAMP for Mac/Linux (to run PHP, MySQL, and Apache)

---

## 🚀 Getting Started

**1.Install dependencies**  
- run "composer install" 
- run "npm install"


### 2.Set up environment
- copy .env.example and create .env

Generate the application key:
  "php artisan key:generate"

### 3.Create the database
- Create a new database using phpMyAdmin

- Open the .env file in your project

- Set the DB_DATABASE, DB_USERNAME, and DB_PASSWORD to match your database

### 4.Run migrations and seed data
- "php artisan migrate --seed"

### 5.Start the development server
- "php artisan serve"
- "npm run dev"

🔑 Google API Credentials
You need to generate your own Google API credentials and add them to the .env file.

📄 Contribution & License
This project is open-source and shared freely.
You can use, modify, and improve it — just give credit if you find it useful!


Note: For mobile payment in cashier role: A QR code is shown (currently for display only, not connected to actual payment)
