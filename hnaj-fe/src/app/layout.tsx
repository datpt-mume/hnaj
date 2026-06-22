import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'HNaj — Khám phá địa điểm quanh bạn',
  description:
    'HNaj giúp bạn tìm địa điểm ăn uống, vui chơi phù hợp với ngữ cảnh chỉ trong một chạm. Nhanh gọn, chính xác, bất ngờ.',
  keywords: ['địa điểm', 'ăn uống', 'gợi ý', 'khám phá', 'HNaj', 'Hà Nội'],
  openGraph: {
    title: 'HNaj — Khám phá địa điểm quanh bạn',
    description: 'Gợi ý địa điểm theo ngữ cảnh — Nhanh gọn, chính xác, bất ngờ.',
    type: 'website',
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="vi">
      <body className="min-h-screen">
        {children}
      </body>
    </html>
  );
}
