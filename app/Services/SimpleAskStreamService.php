<?php

declare(strict_types=1);

namespace App\Services;

use Generator;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;

class SimpleAskStreamService
{
    public const DEFAULT_MODEL = 'openai/gpt-5-mini';

    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->baseUrl = rtrim(config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
    }

    public function streamToOutput(array $messages, ?string $model = null, float $temperature = 1.0, ?string $reasoningEffort = null): string
    {
        $full = '';
        $response = $this->sendStreamRequest($messages, $model, $temperature, $reasoningEffort);

        if ($response->failed()) {
            echo '[ERROR] ' . $response->json('error.message', 'HTTP Error');
            $this->flush();
            return $full;
        }

        foreach ($this->parseSSEStream($response->toPsrResponse()->getBody()) as $event) {
            if ($event['type'] === 'error') {
                echo '[ERROR] ' . $event['data'];
                $this->flush();
                return $full;
            }
            if ($event['type'] === 'content' && $event['data']) {
                echo $event['data'];
                $full .= $event['data']; // on accumule pour pouvoir sauvegarder ensuite
                $this->flush();
            }
            if ($event['type'] === 'reasoning' && $event['data']) {
                echo '[REASONING]' . $event['data'] . '[/REASONING]';
                $this->flush();
            }
        }

        return $full;
    }

    private function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function sendStreamRequest(array $messages, ?string $model, float $temperature, ?string $reasoningEffort): \Illuminate\Http\Client\Response
    {
        $payload = [
            'model' => $model ?? self::DEFAULT_MODEL,
            'messages' => [$this->getSystemPrompt(), ...$messages],
            'temperature' => $temperature,
            'stream' => true,
        ];

        if ($reasoningEffort !== null) {
            $payload['reasoning'] = ['effort' => $reasoningEffort];
        }

        return Http::withToken($this->apiKey)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
            ->withOptions(['stream' => true])
            ->timeout(120)
            ->post("{$this->baseUrl}/chat/completions", $payload);
    }

    private function parseSSEStream(StreamInterface $body): Generator
    {
        $buffer = '';
        while (!$body->eof()) {
            $buffer .= $body->read(1024);
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                if ($event = $this->parseSSELine($line)) {
                    yield $event;
                }
            }
        }
    }

    private function parseSSELine(string $line): ?array
    {
        if ($line === '' || str_starts_with($line, ':')) {
            return null;
        }
        if (!str_starts_with($line, 'data: ')) {
            return null;
        }
        $data = substr($line, 6);
        if ($data === '[DONE]') {
            return ['type' => 'done', 'data' => null];
        }
        return $this->parseJSON($data);
    }

    private function parseJSON(string $json): ?array
    {
        try {
            $parsed = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (isset($parsed['error'])) {
                return ['type' => 'error', 'data' => $parsed['error']['message'] ?? 'Unknown error'];
            }
            $delta = $parsed['choices'][0]['delta'] ?? [];
            if (!empty($delta['content'])) {
                return ['type' => 'content', 'data' => $delta['content']];
            }
            if (!empty($delta['reasoning'])) {
                return ['type' => 'reasoning', 'data' => $delta['reasoning']];
            }
            if (!empty($delta['reasoning_content'])) {
                return ['type' => 'reasoning', 'data' => $delta['reasoning_content']];
            }
            return null;
        } catch (\JsonException) {
            return null;
        }
    }

   private function getSystemPrompt(): array
    {
        $user = auth()->user();
        $now = now()->locale('fr')->format('l d F Y H:i');

        $content = view('prompts.system', [
            'now' => $now,
            'user' => $user?->name ?? 'l\'utilisateur',
        ])->render();

        $instruction = $user?->customInstruction;
        if ($instruction && $instruction->is_enabled) {
            $extra = '';
            if (filled($instruction->about_you)) {
                $extra .= "\n\n## À propos de l'utilisateur\n" . $instruction->about_you;
            }
            if (filled($instruction->behavior)) {
                $extra .= "\n\n## Comportement souhaité\n" . $instruction->behavior;
            }
            if ($extra !== '') {
                $content .= "\n\n---\n# Instructions personnalisées de l'utilisateur\nApplique-les tout en restant GameSage :" . $extra;
            }
        }

        return ['role' => 'system', 'content' => $content];
    }
}