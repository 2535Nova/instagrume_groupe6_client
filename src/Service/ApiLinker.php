<?php

namespace App\Service;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiLinker {

	private $baseURL = 'http://127.0.0.1:3000/api';
	private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client->withOptions([
			'no_proxy' => '127.0.0.1'
		]);
    }

	public function postData($url, $data, $token) {
		$response = $this->client->request('POST', $this->baseURL . $url, [
			'body' => $data,
			'headers' => [
				'Authorization' => 'Bearer ' . $token
			]
		]);
		$content = $response->getContent();	
		
		return $content;
	}

	public function getData($url, $token) {
		$response = $this->client->request('GET', $this->baseURL . $url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $token
			]
		]);
		$content = $response->getContent();
		
		return $content;
	}

	public function putData($url, $data, $token) {
		$response = $this->client->request('PUT', $this->baseURL . $url, [
			'body' => $data,
			'headers' => [
				'Authorization' => 'Bearer ' . $token
			]
		]);				
		$content = $response->getContent();	
		
		return $content;
	}

	public function deleteData($url, $token) {
		$response = $this->client->request('DELETE', $this->baseURL . $url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $token
			]
		]);
		$content = $response->getContent();
		
		return $content;
	}	
}