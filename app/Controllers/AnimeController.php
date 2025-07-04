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
                'image'    => [
                    'rules' => 'uploaded[image]|max_size[image,2048]|is_image[image]',
                    'label' => 'Image'
                ]
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->respond(['errors' => $validation->getErrors()], 400);
            }

            $image = $this->request->getFile('image');
            $uploadPath = WRITEPATH . "uploads/anime_user_$userId";
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $ext = $image->getClientExtension();
            $tempName = 'temp_' . time() . ".$ext";
            if (!$image->move($uploadPath, $tempName)) {
                throw new RuntimeException('Failed to save image');
            }

            $db = new Anime();
            $db->setTable("anime_user_{$userId}");            
            $animeId = $db->insert([
                'title'    => esc($this->request->getVar('title')),
                'type'     => esc($this->request->getVar('type')),
                'seasons'  => (int)$this->request->getVar('seasons'),
                'episodes' => (int)$this->request->getVar('episodes'),
                'watched'  => (int)$this->request->getVar('watched'),
                'status'   => esc($this->request->getVar('status')),
                'image'    => ''
            ]);

            if (!$animeId) {
                @unlink("$uploadPath/$tempName");
                throw new RuntimeException('Database insert failed');
            }

            $finalName = "$animeId.$ext";
            rename("$uploadPath/$tempName", "$uploadPath/$finalName");            
            $db->update($animeId, ['image' => "anime_user_$userId/$finalName"]);
            return $this->respond(['message' => 'Anime created successfully'], 201);

        } catch (\Exception $e) {
            log_message('error', 'Anime creation failed: ' . $e->getMessage());
            return $this->respond(['error' => $e->getMessage()], 500);
        }
    }

    private function getUserIdByEmail($email)
    {
        $userModel = new AuthUser();
        $user = $userModel->where('email', $email)->first();
        return $user['id'] ?? null;
    }
}