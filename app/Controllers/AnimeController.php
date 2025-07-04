<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\AuthUser;
use App\Models\Anime;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class AnimeController extends ResourceController
{
    public function create()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->respond(['error' => 'Missing or invalid Authorization header'], 401);
        }

        try {
            $decoded = JWT::decode($matches[1], new Key(getenv('JWT_SECRET'), 'HS256'));
            $userId = $this->getUserIdByEmail($decoded->email);

            $validation = \Config\Services::validation();
            $validation->setRules([
                'title'    => 'required|max_length[255]',
                'type'     => 'permit_empty|max_length[50]',
                'seasons'  => 'permit_empty|integer',
                'episodes' => 'permit_empty|integer',
                'watched'  => 'permit_empty|integer',
                'status'   => 'permit_empty|max_length[50]',
                'image'    => 'uploaded[image]|max_size[image,2048]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond(['errors' => $validation->getErrors()], 400);
            }

            $image = $this->request->getFile('image');
            if (!$image->isValid()) {
                return $this->respond(['error' => 'Invalid image upload'], 400);
            }

            $newName = $image->getRandomName();
            if (!$image->move(WRITEPATH . 'uploads/anime', $newName)) {
                return $this->respond(['error' => 'Failed to save image'], 500);
            }

            $data = [
                'title'    => esc($this->request->getVar('title')),
                'type'     => esc($this->request->getVar('type')),
                'seasons'  => (int)$this->request->getVar('seasons'),
                'episodes' => (int)$this->request->getVar('episodes'),
                'watched'  => (int)$this->request->getFile('watched'),
                'status'   => esc($this->request->getVar('status')),
                'image'    => $newName
            ];

            $db = new Anime();
            $db->setTable("if037599865_dev_anime.anime_{$userId}");
            if (!$db->insert($data)) {
                @unlink(WRITEPATH . 'uploads/anime/' . $newName);
                throw new \RuntimeException('Failed to insert anime record');
            }

            return $this->respond(['message' => 'Anime added successfully!'], 200);

        } catch (\Exception $e) {
            log_message('error', 'Anime creation failed: ' . $e->getMessage());
            return $this->respond(['error' => 'Failed to process request'], 500);
        }
    }

    private function getUserIdByEmail($email)
    {
        $userModel = new AuthUser();
        $user = $userModel->where('email', $email)->first();
        return $user['id'] ?? null;
    }
}