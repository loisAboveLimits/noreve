<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Registry {
    public static function all(): array {
        $list = [
            'google'     => [ 'label' => 'Google', 'pro' => false ],
            'facebook'   => [ 'label' => 'Facebook', 'pro' => false ],
            'github'     => [ 'label' => 'GitHub', 'pro' => false ],
            'microsoft'  => [ 'label' => 'Microsoft', 'pro' => false ],
            'linkedin'   => [ 'label' => 'LinkedIn', 'pro' => false ],
            'slack'      => [ 'label' => 'Slack', 'pro' => false ],
            'discord'    => [ 'label' => 'Discord', 'pro' => false ],
            'amazon'     => [ 'label' => 'Amazon', 'pro' => false ],
            'yahoo'      => [ 'label' => 'Yahoo', 'pro' => false ],
            'wordpress'  => [ 'label' => 'WordPress.com', 'pro' => false ],
            'spotify'    => [ 'label' => 'Spotify', 'pro' => false ],
            'line'       => [ 'label' => 'LINE', 'pro' => false ],
            'twitter'    => [ 'label' => 'X', 'pro' => false ],
            'twitch'     => [ 'label' => 'Twitch', 'pro' => false ],
            'reddit'     => [ 'label' => 'Reddit', 'pro' => false ],
            'tiktok'     => [ 'label' => 'TikTok', 'pro' => false ],
            // Token-based providers (available in Free; Pro gates integration contexts).
            'magic_link' => [ 'label' => 'Magic Link', 'pro' => false ],
            'otp_email'  => [ 'label' => 'OTP (Email)', 'pro' => false ],
        ];
        return $list;
    }
}
