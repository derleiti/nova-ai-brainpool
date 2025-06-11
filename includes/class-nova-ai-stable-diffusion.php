<?php
class Nova_AI_Stable_Diffusion {
    public static function generate_image($prompt) {
        $payload = json_encode([
            "prompt" => $prompt,
            "negative_prompt" => "nsfw, nude, violence, gore",
            "steps" => 30,
            "cfg_scale" => 7.5,
            "width" => 1920,
            "height" => 1080,
            "sampler_name" => "Euler a"
        ]);

        $ch = curl_init("http://localhost:7860/sdapi/v1/txt2img");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if (!isset($result['images'][0])) return false;

        $image_data = base64_decode($result['images'][0]);
        $filename = 'nova-ai-' . time() . '.png';
        $filepath = plugin_dir_path(__FILE__) . '../assets/' . $filename;

        file_put_contents($filepath, $image_data);
        return 'assets/' . $filename;
    }
}
?>
