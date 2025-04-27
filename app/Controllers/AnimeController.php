<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class AnimeController extends ResourceController
{
    public function create()
    {
        $key = getenv('JWT_SECRET');
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->respond(['error' => 'Missing or invalid Authorization header'], 401);
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $email = $decoded->email ?? null;

            $db = \Config\Database::connect();
            $userId = $this->getUserIdByEmail($email); 
            $tableName = "anime_{$userId}";

            $title    = $this->request->getVar('title');
            $type     = $this->request->getVar('type');
            $seasons  = $this->request->getVar('seasons');
            $episodes = $this->request->getVar('episodes');
            $watched  = $this->request->getVar('watched');
            $status   = $this->request->getVar('status');
            $image    = $this->request->getFile('image');

            $imageName = null;
            if ($image && $image->isValid() && !$image->hasMoved()) {
                $imageName = $image->getRandomName();
                $image->move('uploads/anime', $imageName);
            }

            $builder = $db->table($tableName);
            $builder->insert([
                'title'    => $title,
                'type'     => $type,
                'image'    => $imageName,
                'seasons'  => $seasons,
                'episodes' => $episodes,
                'watched'  => $watched,
                'status'   => $status,
            ]);

            return $this->respond(['message' => 'Anime added successfully!'], 200);
        } 
        
        catch (\Exception $e) {
            return $this->respond(['error' => 'Invalid token'], 401);
            
        }
    }

    private function getUserIdByEmail($email)
    {
        $userModel = new \App\Models\AuthUser();
        $user = $userModel->where('email', $email)->first();
        return $user['id'] ?? null;
    }
}