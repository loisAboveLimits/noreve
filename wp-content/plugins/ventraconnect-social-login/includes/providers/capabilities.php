<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class VCS_Provider_Capabilities {
    public static function get( string $slug ): array {
        $map = [
            'google'   => [ 'name'=>1,'email'=>1,'avatar'=>1,'first_name'=>1,'last_name'=>1,'locale'=>1,'profile_url'=>1,'website'=>1 ],
            'facebook' => [ 'name'=>1,'email'=>1,'avatar'=>1,'first_name'=>1,'last_name'=>1,'locale'=>1,'profile_url'=>1,'website'=>1,'location'=>1 ],
            'linkedin' => [ 'name'=>1,'email'=>1,'avatar'=>1,'first_name'=>1,'last_name'=>1,'profile_url'=>1,'headline'=>1,'website'=>1,'location'=>1 ],
            'github'   => [ 'name'=>1,'email'=>1,'avatar'=>1,'profile_url'=>1,'company'=>1,'website'=>1,'location'=>1,'first_name'=>1,'last_name'=>1 ],
            'microsoft'=> [ 'name'=>1,'email'=>1,'avatar'=>1,'first_name'=>1,'last_name'=>1,'locale'=>1,'profile_url'=>1 ],
            'slack'    => [ 'name'=>1,'email'=>1,'avatar'=>1,'locale'=>1,'first_name'=>1,'last_name'=>1 ],
            'discord'  => [ 'name'=>1,'email'=>1,'avatar'=>1,'locale'=>1 ],
            'spotify'  => [ 'name'=>1,'email'=>1,'avatar'=>1,'profile_url'=>1,'first_name'=>1,'last_name'=>1 ],
            'wordpress'=> [ 'name'=>1,'email'=>1,'avatar'=>1,'profile_url'=>1,'website'=>1,'first_name'=>1,'last_name'=>1,'locale'=>1 ],
            'yahoo'    => [ 'name'=>1,'email'=>1,'avatar'=>1,'first_name'=>1,'last_name'=>1,'locale'=>1,'profile_url'=>1 ],
            'amazon'   => [ 'name'=>1,'email'=>1,'first_name'=>1,'last_name'=>1 ],
            'twitter'  => [ 'name'=>1,'avatar'=>1,'profile_url'=>1,'website'=>1,'location'=>1 ],
            'tiktok'   => [ 'name'=>1,'avatar'=>1,'profile_url'=>1,'first_name'=>1,'last_name'=>1,'nickname'=>1 ],
            'line'     => [ 'name'=>1,'email'=>1,'avatar'=>1,'first_name'=>1,'last_name'=>1 ],
            'twitch'   => [ 'name'=>1,'email'=>1,'avatar'=>1,'profile_url'=>1 ],
            'reddit'   => [ 'name'=>1,'email'=>1,'avatar'=>1,'profile_url'=>1 ],
        ];
        $slug = strtolower( $slug );
        return (array) ( $map[ $slug ] ?? [] );
    }
}
