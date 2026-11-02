# Vercel + Render Deployment

The React app is deployed to Vercel from the `frontend` directory. Laravel is deployed to Render from the backend repository using the included Dockerfile and `render.yaml` blueprint.

## Run the API locally with Docker Desktop

1. In `backend`, copy `.env.example` to `.env` and set your database connection values. The API container connects to the configured database; this Compose setup does not create a database.
2. Generate an application key with `docker compose build` followed by `docker compose run --rm api php artisan key:generate --show`. Copy the printed key into `APP_KEY` in `.env`.
3. Start the API with `docker compose up --build -d`.
4. Confirm it is ready at [http://localhost:8000/up](http://localhost:8000/up).
5. Run the frontend from `frontend` with `npm run dev`. Its `.env.example` points to `http://localhost:8000/api`; use that value for `VITE_API_BASE_URL` in the frontend `.env`.

The API is available on host port `8000` and maps to Apache's container port `10000`. Stop it with `docker compose down`. If you change backend code, rebuild with `docker compose up --build -d`. Database migrations are not run automatically.

## Render API

1. Push the backend repository to your Git provider and create a Render Blueprint or Web Service from it.
2. Set the Render environment variables:
   - `APP_KEY`: generate locally with `php artisan key:generate --show`; paste the result into Render, never into source control.
   - `APP_URL`: the deployed Render URL, such as `https://pba-api.onrender.com`.
   - `FRONTEND_URL`: `https://pba-online.vercel.app` (already set by the Render blueprint).
   - `DB_HOST`, `DB_USERNAME`, and `DB_PASSWORD`: values from Supabase Connect. Keep these private.
3. Keep `DB_CONNECTION=pgsql`, `DB_PORT=5432`, `DB_DATABASE=postgres`, and `DB_SSLMODE=require`.
4. Wait for the health check at `/up` to pass.

The Render service uses Apache on port 10000 and includes PDO PostgreSQL support. It does not run migrations automatically.
The API CORS configuration allows `https://pba-online.vercel.app`; keep `FRONTEND_URL` set to that origin in Render as well.

## Vercel Frontend

1. Import the frontend repository into Vercel.
2. Set the Vercel project Root Directory to `frontend`.
3. Optionally set `VITE_API_BASE_URL` to the deployed Render API URL ending in `/api`. The frontend defaults to `https://pba-backend-1.onrender.com/api`.
4. Deploy or redeploy the frontend.

`frontend/vercel.json` includes the Vite output and SPA rewrites needed by React Router. No Supabase credentials belong in Vercel.

## Preview Deployments

For Vercel preview URLs, set `CORS_ALLOWED_ORIGIN_PATTERNS` in Render to comma-separated regex patterns matching only your Vercel project’s preview domains. Do not use a pattern that allows every site origin. `FRONTEND_URL` accepts comma-separated exact origins if you prefer to allow a fixed list.

After changing Render environment variables, redeploy the backend so Laravel picks up the configuration. Redeploy the Vercel frontend after setting `VITE_API_BASE_URL`.