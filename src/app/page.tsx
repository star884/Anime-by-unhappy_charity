import Link from 'next/link';

// Sample episode target IDs for demonstration
const SAMPLE_WATCH_LIST = [
  { id: 'one-piece-episode-1', name: 'One Piece - Episode 1' },
  { id: 'naruto-episode-1', name: 'Naruto - Episode 1' },
  { id: 'jujutsu-kaisen-2nd-season-episode-1', name: 'Jujutsu Kaisen S2 - Episode 1' },
];

export default function HomePage() {
  return (
    <main className="max-w-4xl mx-auto p-6 space-y-6">
      <h1 className="text-3xl font-extrabold tracking-tight">Available Streams</h1>
      <p className="text-gray-400">
        Select a stream to trigger the real-time server scraper execution.
      </p>

      <div className="grid gap-4">
        {SAMPLE_WATCH_LIST.map((item) => (
          <Link
            key={item.id}
            href={`/watch/${item.id}`}
            className="p-4 bg-gray-900 border border-gray-800 rounded-xl hover:border-indigo-500 transition duration-200 block"
          >
            <span className="font-semibold">{item.name}</span>
            <span className="block text-xs text-gray-500 mt-1">ID: {item.id}</span>
          </Link>
        ))}
      </div>
    </main>
  );
}
