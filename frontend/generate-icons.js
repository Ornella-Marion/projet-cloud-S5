import fs from 'fs';

const svg192 = `<svg xmlns="http://www.w3.org/2000/svg" width="192" height="192" viewBox="0 0 192 192">
  <rect width="192" height="192" rx="32" fill="#1877f2"/>
  <circle cx="96" cy="70" r="35" fill="white" opacity="0.2"/>
  <text x="96" y="82" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="40">🛣️</text>
  <text x="96" y="125" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="24" font-weight="bold">Road</text>
  <text x="96" y="158" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="24" font-weight="bold">Watch</text>
</svg>`;

const svg512 = `<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
  <rect width="512" height="512" rx="80" fill="#1877f2"/>
  <circle cx="256" cy="180" r="90" fill="white" opacity="0.2"/>
  <text x="256" y="210" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="100">🛣️</text>
  <text x="256" y="330" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="64" font-weight="bold">Road</text>
  <text x="256" y="410" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="64" font-weight="bold">Watch</text>
</svg>`;

fs.mkdirSync('public/icons', { recursive: true });
fs.writeFileSync('public/icons/icon-192x192.png', svg192);
fs.writeFileSync('public/icons/icon-512x512.png', svg512);
console.log('Icons created successfully!');
