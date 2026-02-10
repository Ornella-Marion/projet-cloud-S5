import { getMessaging, getToken, onMessage, isSupported } from 'firebase/messaging';
import app from '../firebase';

let messaging: ReturnType<typeof getMessaging> | null = null;

export async function initFCM() {
  if (!(await isSupported())) return null;
  messaging = getMessaging(app);
  return messaging;
}

export async function requestNotificationPermission(): Promise<boolean> {
  if (!('Notification' in window)) return false;
  const permission = await Notification.requestPermission();
  return permission === 'granted';
}

export async function getFCMToken(vapidKey?: string): Promise<string|null> {
  if (!messaging) await initFCM();
  if (!messaging) return null;
  try {
    return await getToken(messaging, { vapidKey });
  } catch (e) {
    return null;
  }
}

export function onFCMMessage(callback: (payload: any) => void) {
  if (!messaging) return;
  onMessage(messaging, callback);
}
