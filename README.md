# Anime Stream Engine

Improved version of the Anime streaming demo. This project provides a small full-stack app that:

- Searches anime metadata via Jikan (public API)
- Attempts to map metadata to streaming providers (Consumet or similar)
- Proxies provider watch endpoints to return HLS manifest URLs to the frontend
- Plays HLS using hls.js in the browser

This rewrite improves security, caching, error handling, and client UX.

## Setup

1. Install dependencies

```bash
npm install
```

2. Create an `.env` file (optional) or use environment variables. See `.env.example` for defaults.

3. Start the server

```bash
npm start
```

4. Open http://localhost:3000

## Environment variables

- PORT=3000
- CACHE_TTL=3600
- RATE_LIMIT_MAX=120
- JIKAN_BASE=https://api.jikan.moe/v4
- CONSUMET_BASE=https://consumet.org

## Notes

- Provider APIs like Consumet change shape frequently. If streams don't work, collect an example provider JSON response and update the parsing heuristics in `server.js` and `public/app.js`.
- For production, restrict CORS origins, use a persistent cache (Redis), and consider adding authentication.

## Next steps

- Add Dockerfile / docker-compose for easier deployments
- Add tests (Jest + Supertest) for server endpoints
- Add user accounts and watchlists

