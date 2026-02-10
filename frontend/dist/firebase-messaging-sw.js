importScripts('https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "AIzaSyBZkt2K-MTItsrwGLZc4cQf9mvG9tFtLvY",
  authDomain: "fir-d85b1.firebaseapp.com",
  projectId: "fir-d85b1",
  storageBucket: "fir-d85b1.appspot.com",
  messagingSenderId: "474848981698",
  appId: "1:474848981698:web:5716740b8eb96f45c0015b",
  measurementId: "G-4QWZRF4N6H"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
  console.log('[firebase-messaging-sw.js] Received background message ', payload);
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/icons/icon-192x192.png'
  };
  self.registration.showNotification(notificationTitle, notificationOptions);
});
