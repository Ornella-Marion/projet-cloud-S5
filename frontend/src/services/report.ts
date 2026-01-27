import api from './api';


export interface ReportPayload {
  target_type: string;
  report_date: string;
  reason: string;
  road_id?: number | null;
}


export async function createReport(payload: ReportPayload) {
  return api.post('/reports', payload);
}

export async function getReports() {
  return api.get('/reports');
}
