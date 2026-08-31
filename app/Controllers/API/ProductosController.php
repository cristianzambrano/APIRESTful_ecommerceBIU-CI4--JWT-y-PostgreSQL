<?php
namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\ProductoModel;
use CodeIgniter\API\ResponseTrait;

class ProductosController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        return $this->respond(['data' => (new ProductoModel())
            ->where('activo', true)->orderBy('id', 'DESC')->findAll()]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data['nombre']) || !isset($data['precio']) || !isset($data['stock'])) {
            return $this->failValidationErrors('nombre, precio y stock son obligatorios.');
        }
        if (!is_numeric($data['precio']) || $data['precio'] < 0 || !is_int($data['stock']) || $data['stock'] < 0) {
            return $this->failValidationErrors('Precio o stock no válidos.');
        }

        $data['creado_por'] = (int) $this->request->user['sub'];
        $id = (new ProductoModel())->insert($data, true);
        return $this->respondCreated(['mensaje' => 'Producto creado.', 'data' => ['id' => $id]]);
    }

    public function update($id)
    {
        $model = new ProductoModel();
        if (!$model->find($id)) return $this->failNotFound('Producto no encontrado.');
        $data = $this->request->getJSON(true);
        unset($data['id'], $data['creado_por'], $data['creado_en']);
        $model->update($id, $data);
        return $this->respond(['mensaje' => 'Producto actualizado.', 'data' => $model->find($id)]);
    }

    public function delete($id)
    {
        $model = new ProductoModel();
        if (!$model->find($id)) return $this->failNotFound('Producto no encontrado.');
        $model->update($id, ['activo' => false]);
        return $this->respond(['mensaje' => 'Producto desactivado.']);
    }
}

