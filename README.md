PS Rehab - Real-time Patient Queue Management System
ระบบบริหารจัดการคิวผู้ป่วยแบบ Real-time สำหรับ PS Rehab Center พัฒนาขึ้นเพื่อเพิ่มประสิทธิภาพการทำงานร่วมกันระหว่างแผนกต่างๆ ตั้งแต่เคาน์เตอร์, นักกายภาพบำบัด, ไปจนถึงแพทย์ พร้อมทั้งแสดงผลสถานะคิวให้คนไข้ทราบผ่านหน้าจอ Digital Signage

✨ Key Features (คุณสมบัติหลัก)
Real-time Dashboard: หน้าจออัปเดตสถานะคนไข้ให้ทุกแผนกเห็นตรงกันทันทีโดยไม่ต้อง Refresh ด้วยเทคโนโลยี Server-Sent Events (SSE)

Role-Based Access: หน้าจอและฟังก์ชันการทำงานจะปรับเปลี่ยนไปตามสิทธิ์ของผู้ใช้ (เคาน์เตอร์, นักกายภาพ, แพทย์)

JERA API Integration: ซิงค์ข้อมูลคิวคนไข้ของวันปัจจุบันจาก JERA API เข้าสู่ระบบโดยอัตโนมัติ

External API Authentication: ระบบ Login และการดึงรายชื่อบุคลากรเชื่อมต่อกับ API ภายนอกเพื่อความปลอดภัยและข้อมูลที่เป็นหนึ่งเดียว

Flexible Workflow:

นักกายภาพสามารถดึงคนไข้ใหม่จากคิวกลางมาเริ่มทำกายภาพได้โดยตรง

แพทย์สามารถเห็นคิวคนไข้ของตนเองได้ทันทีที่เคาน์เตอร์หรือนักกายภาพระบุชื่อ

แพทย์สามารถส่งเคสกลับไปให้นักกายภาพ (ทั้งคิวกลางและคิวส่วนตัว) หรือส่งต่อไปยังแผนกการเงินได้

นักกายภาพสามารถจบงานและส่งเคสต่อไปยังแผนกการเงินได้โดยตรง

เคาน์เตอร์สามารถจบเคสและรับชำระเงินได้ทุกขั้นตอน (Override)

Automated Queue Clearing: ระบบจะตรวจสอบและเคลียร์คิวคนไข้ที่ชำระเงินเรียบร้อยแล้ว (หายไปจาก JERA API) ให้โดยอัตโนมัติ

Sound & Visual Notifications: มีเสียงและ Animation กระพริบแจ้งเตือนเมื่อมีงานใหม่เข้ามาในแต่ละแผนก

Digital Signage: หน้าจอแสดงผลสำหรับคนไข้ภายนอก แสดงสถานะคิวของแพทย์แต่ละท่าน พร้อม Animation แจ้งเตือนเมื่อถูกเรียกตรวจ

Action Logging: บันทึกทุกขั้นตอนการทำงานของเจ้าหน้าที่ และมีหน้ารายงานสำหรับให้เคาน์เตอร์ตรวจสอบย้อนหลังได้

🛠️ Technology Stack (เทคโนโลยีที่ใช้)
Backend: PHP 8+

Frontend: HTML5, CSS3, JavaScript (ES6)

Database: MySQL / MariaDB

Libraries:

Bootstrap 5 (UI Framework)

jQuery (DOM Manipulation & AJAX)

HTTP_Request2 (PHP HTTP Client)

Dependency Manager: Composer

📂 Project Structure (โครงสร้างไฟล์)
/
|-- api/                  # สคริปต์ Backend ทั้งหมด
|   |-- action_handler.php    # จัดการ Action จากผู้ใช้
|   |-- data_handler.php      # ดึงข้อมูลครั้งแรก
|   |-- helpers.php           # ฟังก์ชันเชื่อมต่อ API ภายนอก
|   |-- signage_data.php      # API สำหรับหน้าจอ Signage
|   |-- stream.php            # หัวใจของระบบ Real-time (SSE)
|-- assets/
|   |-- sounds/             # ไฟล์เสียงแจ้งเตือน
|       |-- notification.mp3
|       |-- payment.mp3
|-- configs/
|   |-- DB.php              # ตั้งค่าการเชื่อมต่อ DB และ API
|-- css/
|   |-- style.css           # CSS สำหรับ Dashboard
|   |-- signage.css         # CSS สำหรับ Signage
|-- js/
|   |-- script.js           # Javascript สำหรับ Dashboard
|   |-- signage.js          # Javascript สำหรับ Signage
|-- layout/
|   |-- _header.php
|   |-- _footer.php
|-- views/
|   |-- view_counter.php
|   |-- view_therapist.php
|   |-- view_doctor.php
|-- vendor/                 # โฟลเดอร์ของ Composer
|-- auth.php                # สคริปต์จัดการ Login
|-- composer.json           # ไฟล์ตั้งค่า Composer
|-- database_schema.sql     # โครงสร้างฐานข้อมูล
|-- index.php               # หน้าหลัก Dashboard
|-- login.php               # หน้า Login
|-- logout.php              # สคริปต์ Logout
|-- log_report.php          # หน้ารายงาน Log
|-- signage.php             # หน้าจอ Digital Signage
|-- README.md               # ไฟล์นี้

🚀 Setup & Installation (การติดตั้ง)
Clone Repository:

git clone [your-repository-url]
cd [your-project-folder]

Install Dependencies:
ตรวจสอบให้แน่ใจว่าคุณมี Composer ติดตั้งแล้ว จากนั้นรันคำสั่ง:

composer install
