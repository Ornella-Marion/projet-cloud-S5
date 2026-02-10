import { db } from '../firebase';
import { collection, addDoc, Timestamp } from 'firebase/firestore';


export interface FirestoreReport {
  user_id: number;
  target_type: string;
  report_date: string;
  reason: string;
  road_id?: number | null;
  created_at?: any;
}


export async function addReportToFirestore(report: FirestoreReport) {
  return addDoc(collection(db, 'reports'), {
    ...report,
    created_at: Timestamp.now(),
  });
}
