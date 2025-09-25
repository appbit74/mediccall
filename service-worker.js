// service-worker.js
self.addEventListener('push', function(event) {
    const data = event.data.json(); // รับข้อมูลที่ส่งมาจาก Backend

    const title = data.title || 'PS Medical Infomation System';
    const options = {
        body: data.body,
        icon: 'assets/icons/icon-192.png', // ไอคอนที่จะแสดง
        badge: 'assets/icons/badge.png',
        sound: 'assets/sounds/notification.mp3', // เสียง (อาจไม่ทำงานบนทุกอุปกรณ์)
        vibrate: [200, 100, 200] // การสั่น
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    // โค้ดสำหรับจัดการเมื่อผู้ใช้คลิกที่การแจ้งเตือน เช่น เปิดหน้าเว็บ
    event.waitUntil(clients.openWindow('/'));
});