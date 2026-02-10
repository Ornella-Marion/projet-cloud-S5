
import { initializeApp } from 'firebase/app';
import { getAuth } from 'firebase/auth';
import { getFirestore } from 'firebase/firestore';

const firebaseConfig = {
  apiKey: "AIzaSyBZkt2K-MTItsrwGLZc4cQf9mvG9tFtLvY",
  authDomain: "fir-d85b1.firebaseapp.com",
  projectId: "fir-d85b1",
  storageBucket: "fir-d85b1.appspot.com",
  messagingSenderId: "474848981698",
  appId: "1:474848981698:web:5716740b8eb96f45c0015b",
  measurementId: "G-4QWZRF4N6H"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Authentification Firebase
export const auth = getAuth(app);

// Firestore
export const db = getFirestore(app);

export default app;