<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Swagger UI用カスタムコントローラー
 * 
 * YAMLファイルを直接読み込んでSwagger UIに表示します。
 */
class SwaggerController extends Controller
{
    /**
     * Swagger UIを表示
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('swagger.index');
    }

    /**
     * OpenAPI仕様書（YAML）を返却
     * 
     * @return \Illuminate\Http\Response
     */
    public function yaml()
    {
        $yamlPath = base_path('docs/api/openapi.yaml');
        
        if (!file_exists($yamlPath)) {
            abort(404, 'OpenAPI仕様書が見つかりません');
        }
        
        $yaml = file_get_contents($yamlPath);
        
        return Response::make($yaml, 200, [
            'Content-Type' => 'application/x-yaml',
            'Content-Disposition' => 'inline; filename="openapi.yaml"',
        ]);
    }
}
