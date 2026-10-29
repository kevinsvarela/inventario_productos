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
            $productos = ProductoModel::query()
                ->select(['id', 'sku', 'nombre_producto', 'descripcion', 'precio_unitario', 'stock_actual', 'categoria', 'updated_at'])
                ->find($args['id']); #find busca un elemento
 
            if ($productos === null) {
                return $this->json($response, ['error' => 'Producto no encontrado.'], 404);
            }
 
            return $this->json($response, ['data' => $productos], 200);
        }
 
        $productos = ProductoModel::query()
                ->select(['id', 'sku', 'nombre_producto', 'descripcion', 'precio_unitario', 'stock_actual', 'categoria', 'updated_at'])
                ->get()
                ->toArray();
            return $this->json($response, ['data' => $productos], 200);
    }
 
    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody(); #convierte los datos en arreglo
 
        $data = is_array($data) ? $data : []; #si es null, asigna un arreglo vacío
 
        $errors = [];
        $sku = trim((string) ($data['sku'] ?? ''));
        $productName = trim((string) ($data['nombre_producto'] ?? ''));
        $description = isset($data['descripcion']) ? trim((string) $data['descripcion']) : null; #el isset verifica si la variable existe, si no existe, asigna null
        $unitPrice = $data['precio_unitario'] ?? '';
        $currentStock = filter_var($data['stock_actual'] ?? '', FILTER_VALIDATE_INT);
        $category = trim((string) ($data['categoria'] ?? ''));
 
 
        if ($sku === '' || mb_strlen($sku) > 50) { #mb_strlen() cuenta los caracteres de una cadena, considerando caracteres multibyte
            $errors['sku'] = 'Es obligatorio y debe tener máximo 50 caracteres.';
        }
        if ($productName === '' || mb_strlen($productName) > 100) {
            $errors['nombre_producto'] = 'Es obligatorio y debe tener máximo 100 caracteres.';
        }
        if (!is_numeric($unitPrice) || $unitPrice < 0) {
            $errors['precio_unitario'] = 'Es obligatorio, debe ser numérico y no puede ser negativo.';
        }
        if ($currentStock === false || $currentStock < 0) {
            $errors['stock_actual'] = 'Es obligatorio y debe ser un número entero mayor o igual a 0.';
        }
        if ($category === '' || mb_strlen($category) > 50) {
            $errors['categoria'] = 'Es obligatoria y debe tener máximo 50 caracteres.';
        }
        if ($description !== null && $description === '') {
            $description = null;
        }
 
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }
 
        try {
            $this->container->get('eloquent');
            $productos = ProductoModel::create([
                'sku' => $sku,
                'nombre_producto' => $productName,
                'descripcion' => $description,
                'precio_unitario' => $unitPrice,
                'stock_actual' => $currentStock,
                'categoria' => $category,
            ]);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #1062 es el código de error de MySQL por duplicate entry
                return $this->json($response, ['error' => 'El SKU ya está registrado.'], 409);
            }
 
            throw $exception;
        }
 
        return $this->json($response, ['data' => $productos->toArray()], 201); #201 significa que el recurso fue creado exitosamente
    }
 
    public function update(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $producto = ProductoModel::find($args['id']); #ORM, busca el producto
 
            if ($producto === null) {
                return $this->json($response, ['error' => 'Producto no encontrado.'], 404);
            }
 
            $data = $request->getParsedBody();
            $data = is_array($data) ? $data : [];
 
            if (isset($data['sku'])) {
                $sku = trim((string) $data['sku']);
                if ($sku === '' || mb_strlen($sku) > 50) {
                    return $this->json($response, ['error' => 'SKU inválido.'], 422);
                }
                $producto->sku = $sku;
            }
 
            if (isset($data['nombre_producto'])) {
                $productName = trim((string) $data['nombre_producto']);
                if ($productName === '' || mb_strlen($productName) > 100) {
                    return $this->json($response, ['error' => 'Nombre del producto inválido.'], 422);
                }
                $producto->nombre_producto = $productName;
            }
 
            if (isset($data['descripcion'])) {
                $description = trim((string) $data['descripcion']);
                $producto->descripcion = $description === '' ? null : $description;
            }
 
            if (isset($data['precio_unitario'])) {
                $unitPrice = $data['precio_unitario'];
                if (!is_numeric($unitPrice) || $unitPrice < 0) {
                    return $this->json($response, ['error' => 'Precio unitario inválido.'], 422);
                }
                $producto->precio_unitario = $unitPrice;
            }
 
            if (isset($data['stock_actual'])) {
                $currentStock = filter_var($data['stock_actual'], FILTER_VALIDATE_INT);
                if ($currentStock === false || $currentStock < 0) {
                    return $this->json($response, ['error' => 'Stock actual inválido.'], 422);
                }
                $producto->stock_actual = $currentStock;
            }
 
            if (isset($data['categoria'])) {
                $category = trim((string) $data['categoria']);
                if ($category === '' || mb_strlen($category) > 50) {
                    return $this->json($response, ['error' => 'Categoría inválida.'], 422);
                }
                $producto->categoria = $category;
            }
 
            try {
                $producto->save();
            } catch (QueryException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) === 1062) { #el SKU nuevo ya lo tiene otro producto
                    return $this->json($response, ['error' => 'El SKU ya está registrado.'], 409);
                }
 
                throw $exception;
            }
 
            return $this->json($response, ['data' => $producto->toArray()], 200);
        }
    }
 
    public function delete(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');
        if (isset($args['id'])) {
            $producto = ProductoModel::find($args['id']); #ORM, busca el producto
 
            if ($producto === null) {
                return $this->json($response, ['error' => 'Producto no encontrado.'], 404);
            }
 
            $producto->delete();
 
            return $this->json($response, ['message' => 'Producto eliminado correctamente.'], 200);
        }
    }
 
 
    private function json(Response $response, array $payload, int $status): Response{
    $response->getBody()->write(json_encode($payload,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
 
        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }
 
}