<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::query();

        if ($request->has('is_updated')) {
            $query->where('is_updated', filter_var($request->is_updated, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('original_article_id')) {
            $query->where('original_article_id', $request->original_article_id);
        }

        if ($request->has('with_versions') && $request->with_versions) {
            $query->with('updatedVersions');
        }

        $perPage = $request->get('per_page', 15);
        $articles = $query->orderBy('date', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate($perPage);

        return response()->json($articles);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'full_content' => 'nullable|string',
            'link' => 'nullable|url',
            'date' => 'nullable|date',
            'source_url' => 'nullable|url',
            'is_updated' => 'boolean',
            'original_article_id' => 'nullable|exists:articles,id',
            'reference_articles' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $article = Article::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $article
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $article = Article::with(['originalArticle', 'updatedVersions'])->find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $article
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'nullable|string',
            'full_content' => 'nullable|string',
            'link' => 'nullable|url',
            'date' => 'nullable|date',
            'source_url' => 'nullable|url',
            'is_updated' => 'boolean',
            'original_article_id' => 'nullable|exists:articles,id',
            'reference_articles' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $article->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $article->fresh()
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found'
            ], 404);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully'
        ]);
    }

    public function latest(): JsonResponse
    {
        $article = Article::where('is_updated', false)
                          ->orderBy('date', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->first();

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'No articles found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $article
        ]);
    }
}
