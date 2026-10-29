<?php

namespace App\controllers;

use App\Models\ProductoModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;


class Producto {
    private ContainerInterface $container;

    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }

    public function read(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        if(isset($args['id'])){
            $producto = ProductoModel::query()
                ->select(['id', 'sku', 'nombre_producto', 'descripcion', 'precio_unitario', 'stock_actual', 'categoria', 'updated_at'])
                ->find($args['id']);

            if ($producto === null) {
                return $this->json($response, ['error' => 'Producto no encontrado.'], 404);
            }

            return $this->json($response, ['data' => $producto->toArray()], 200);
        }

        $productos = ProductoModel::query()
                ->select(['id', 'sku', 'nombre_producto', 'descripcion', 'precio_unitario', 'stock_actual', 'categoria', 'updated_at'])
                ->orderBy('id')
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $productos], 200);
    }

    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody(); #convierte los datos en arreglo

        $data = is_array($data) ? $data : []; #si es null, asigna un arreglo vacío

        [$campos, $errors] = $this->validar($data, false);

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        try {
            $this->container->get('eloquent');
            $producto = ProductoModel::create($campos);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062 es el código de error de MySQL por duplicate entry
                return $this->json($response, ['error' => 'El SKU ya está registrado.'], 409);
            }

            throw $exception;
        }

        #fresh() vuelve a leer el registro para incluir updated_at generado por MySQL
        return $this->json($response, ['data' => $producto->fresh()->toArray()], 201); #201 significa que el recurso fue creado exitosamente
    }

    public function update(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        $producto = ProductoModel::find($args['id']); #ORM, busca el producto

        if ($producto === null) {
            return $this->json($response, ['error' => 'Producto no encontrado.'], 404);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        #Solo se validan y actualizan los campos que vienen en el body
        [$campos, $errors] = $this->validar($data, true);

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        if ($campos === []) {
            return $this->json($response, ['error' => 'Debe enviar al menos un campo para actualizar.'], 422);
        }

        try {
            $producto->fill($campos);
            $producto->save();
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                return $this->json($response, ['error' => 'El SKU ya está registrado.'], 409);
            }

            throw $exception;
        }

        return $this->json($response, ['data' => $producto->fresh()->toArray()], 200);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $this->container->get('eloquent');
        $producto = ProductoModel::find($args['id']); #ORM, busca el producto

        if ($producto === null) {
            return $this->json($response, ['error' => 'Producto no encontrado.'], 404);
        }

        $producto->delete();

        return $response->withStatus(204); #204 significa que se eliminó y no hay contenido que devolver
    }

    private function json(Response $response, array $payload, int $status): Response{
    $response->getBody()->write(json_encode($payload,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }

}
