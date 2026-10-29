<?php
declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;
use Respect\Validation\Exceptions\ValidationException;

final class ProductoValidator {
    private function reglas(): array {
        return [
            'sku' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(3,50))
                ->regex('/^[A-Z0-9-]{3,50}$/'),
                'mensaje' => 'El SKU es obligatorio, debe tener entre 3 y 50 caracteres y solo letras mayúsculas, números y guiones.',
            ],
            'nombre_producto' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(3,100)),
                'mensaje' => 'El nombre del producto es obligatorio y debe tener entre 3 y 100 caracteres.',
            ],
            'descripcion' => [
                'regla' => v::stringType()
                ->length(v::between(1,1000)),
                'mensaje' => 'La descripción debe ser texto de máximo 1000 caracteres.',
            ],
            'precio_unitario' => [
                'regla' => v::numericVal()
                ->greaterThan(0)
                ->lessThanOrEqual(99999999.99),
                'mensaje' => 'El precio unitario es obligatorio, debe ser numérico y mayor a 0.',
            ],
            'stock_actual' => [
                'regla' => v::intVal()
                ->greaterThanOrEqual(0),
                'mensaje' => 'El stock actual es obligatorio y debe ser un número entero mayor o igual a 0.',
            ],
            'categoria' => [
                'regla' => v::stringType()
                ->notBlank()
                ->length(v::between(3,50))
                ->regex('/^[A-Za-zÑñÁÉÍÓÚáéíóú ]{3,50}$/u'),
                'mensaje' => 'La categoría es obligatoria y debe tener entre 3 y 50 caracteres, solo letras y espacios.',
            ],
        ];
    }

    public function validate(array $data): array {
        $errores = [];

        foreach ($this->reglas() as $campo => $config){
            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];
            if($campo === 'descripcion' && ($valor === null || $valor === '')){
                continue;
            }
            if($regla->validate($valor)->hasFailed()){
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }
}