<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SystemController extends Controller
{
    public function info(Request $request): Response
    {
        try {
            $status = env('APP_DEBUG');
            $version = 2.0;
            $datetime = Carbon::now();
            $date = $datetime->format('Y-m-d');
            $time = $datetime->format('H:i:s');
            
            // Conexão com o banco de dados
            $pdo = DB::connection()->getPdo();
            $driver = DB::getDriverName();
            $database = env('DB_DATABASE');
            $size = null;

            // Obter o tamanho do banco de acordo com o driver
            switch ($driver) {
                case 'mysql':
                    $sizeQuery = DB::select('
                        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                        FROM information_schema.TABLES
                        WHERE table_schema = ?
                    ', [$database]);
                    $size = $sizeQuery[0]->size_mb ?? null;
                    break;

                case 'pgsql':
                    $sizeQuery = DB::select("
                        SELECT pg_database_size(?) / 1024 / 1024 AS size_mb
                    ", [$database]);
                    $size = $sizeQuery[0]->size_mb ?? null;
                    break;

                case 'sqlite':
                    $sizeQuery = DB::select("PRAGMA page_size");
                    $pageSize = $sizeQuery[0]->page_size ?? 0;

                    $sizeQuery = DB::select("PRAGMA page_count");
                    $pageCount = $sizeQuery[0]->page_count ?? 0;

                    $size = round(($pageSize * $pageCount) / 1024 / 1024, 2);
                    break;

                default:
                    $size = 'Driver não suportado';
                    break;
            }

            return response()->json([
                'status' => $status,
                'date' => $date,
                'time' => $time,
                'version' => $version,
                'driver' => $driver,
                'database_size_mb' => $size
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao obter informações do sistema.',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
