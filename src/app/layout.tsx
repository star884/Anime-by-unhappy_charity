import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'Anime Stream Platform',
  description: 'Self-hosted anime streaming app engine',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className="bg-gray-950 text-gray-100 antialiased min-h-screen">
        <header className="border-b border-gray-800 p-4 bg-gray-900/50 backdrop-blur">
          <div className="max-w-7xl mx-auto flex items-center justify-between">
            <a href="/" className="text-xl font-bold tracking-tight text-indigo-400">
              Stream Engine
            </a>
          </div>
        </header>
        {children}
      </body>
    </html>
  );
}
