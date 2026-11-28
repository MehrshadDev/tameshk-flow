<?php

namespace App\Http\Controllers;

use App\Docs\DocumentationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function show(?string $slug = null): View|RedirectResponse
    {
        if ($slug === null || $slug === '') {
            $first = DocumentationRepository::find(null);

            abort_if(! $first, 404);

            return redirect()->route('docs.show', ['slug' => $first['slug']], 301);
        }

        $article = DocumentationRepository::find($slug);

        abort_if(! $article || ! view()->exists($article['view']), 404);

        $neighbors = DocumentationRepository::neighbors($article['slug']);

        return view('pages.docs.show', [
            'sections'  => DocumentationRepository::sections(),
            'article'   => $article,
            'prev'      => $neighbors['prev'],
            'next'      => $neighbors['next'],
        ]);
    }
}
