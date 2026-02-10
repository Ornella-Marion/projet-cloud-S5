import api from './api';
import type { AxiosResponse } from 'axios';


export interface ReportPayload {
  target_type: string;
  report_date: string;
  reason: string;
  road_id?: number | null;
  photos?: (File | Blob)[];
}


export async function createReport(payload: ReportPayload): Promise<AxiosResponse> {
  // Si des photos sont présentes, envoyer en FormData (multipart)
  if (payload.photos && payload.photos.length > 0) {
    const formData = new FormData();
    formData.append('target_type', payload.target_type);
    formData.append('report_date', payload.report_date);
    formData.append('reason', payload.reason);
    if (payload.road_id != null) {
      formData.append('road_id', payload.road_id.toString());
    }
    // Le backend attend 'photo' (une seule image par signalement)
    formData.append('photo', payload.photos[0], 'photo.jpg');
    try {
      return await api.post('/reports', formData);
    } catch (err) {
      // Fallback base64 si l'upload FormData échoue
      return new Promise<AxiosResponse>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
          const base64 = (reader.result as string).split(',')[1];
          const { photos, ...jsonPayload } = payload;
          api.post('/reports', { ...jsonPayload, photo_base64: base64 })
            .then(resolve)
            .catch(reject);
        };
        reader.onerror = reject;
        reader.readAsDataURL(payload.photos![0] as Blob);
      });
    }
  }
  // Sans photo, envoyer en JSON classique
  const { photos, ...jsonPayload } = payload;
  return api.post('/reports', jsonPayload);
}

export async function getReports() {
  return api.get('/reports');
}


/**
 * Compresser une image avant upload
 * Réduit la taille et la qualité pour un upload rapide
 */
export function compressImage(file: File, maxWidth = 1024, quality = 0.7): Promise<Blob> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        const canvas = document.createElement('canvas');
        let width = img.width;
        let height = img.height;

        // Redimensionner si nécessaire
        if (width > maxWidth) {
          height = Math.round((height * maxWidth) / width);
          width = maxWidth;
        }

        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
          reject(new Error('Impossible de créer le contexte canvas'));
          return;
        }
        ctx.drawImage(img, 0, 0, width, height);

        canvas.toBlob(
          (blob) => {
            if (blob) {
              console.log(`📷 Image compressée: ${(file.size / 1024).toFixed(0)}KB → ${(blob.size / 1024).toFixed(0)}KB`);
              resolve(blob);
            } else {
              reject(new Error('Échec de la compression'));
            }
          },
          'image/jpeg',
          quality
        );
      };
      img.onerror = () => reject(new Error('Échec du chargement de l\'image'));
      img.src = e.target?.result as string;
    };
    reader.onerror = () => reject(new Error('Échec de la lecture du fichier'));
    reader.readAsDataURL(file);
  });
}
