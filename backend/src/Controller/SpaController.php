<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the built React SPA for all non-API routes.
 * During development: use `npm run dev` at localhost:5173
 * For production/demo: run `npm run build` inside /frontend, then access localhost:8000
 */
class SpaController extends AbstractController
{
    #[Route('/{any}', name: 'spa_fallback', requirements: ['any' => '^(?!api/).*'], priority: -10)]
    public function index(): Response
    {
        $indexPath = $this->getParameter('kernel.project_dir') . '/public/app/index.html';

        if (!file_exists($indexPath)) {
            return new Response(
                <<<HTML
                <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
                <title>TFGdaw</title><style>
                body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f5f3ff;}
                .box{text-align:center;padding:40px;background:#fff;border-radius:12px;border:1px solid #ddd6fe;max-width:480px;}
                h1{color:#7c3aed;margin-bottom:8px;}code{background:#f5f3ff;padding:3px 8px;border-radius:4px;font-size:.9em;}
                </style></head><body>
                <div class="box">
                  <h1>📚 TFGdaw</h1>
                  <p>El frontend React no está compilado todavía.</p>
                  <p>Ejecuta en la carpeta <code>frontend/</code>:</p>
                  <p><code>npm install &amp;&amp; npm run build</code></p>
                  <hr style="border:1px solid #ddd6fe;margin:20px 0;">
                  <p style="font-size:.85em;color:#6b7280;">O durante desarrollo: <code>npm run dev</code> → <a href="http://localhost:5173">localhost:5173</a></p>
                </div></body></html>
                HTML,
                200,
                ['Content-Type' => 'text/html']
            );
        }

        return new Response(
            file_get_contents($indexPath),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }
}
