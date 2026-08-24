<?php
namespace VentraConnect\SocialLogin\Admin\IntegrationsPreview;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Integrations preview blueprints (moved from Settings::integration_preview_blueprints).
 */
class Blueprints {
	/**
	 * Return blueprint metadata for integrations grouped by category.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions(): array {
		return [
			'community_memberships' => [
				'title'           => __( 'Community & Memberships', 'ventraconnect-social-login' ),
				'description'     => __( 'Enable one-tap login across member-focused plugins.', 'ventraconnect-social-login' ),
				'option_group'    => 'ventraconnect_sl_integrations_memberships',
				'tab_slug'        => 'community_memberships',
				'post_login_note' => __( 'Choose where members land after signing in with social login.', 'ventraconnect-social-login' ),
				'upsell'          => [
					'title'       => __( 'Unlock Community & Membership Integrations', 'ventraconnect-social-login' ),
					'description' => __( 'Pair VentraConnect Social Login with leading community and membership plugins for instant onboarding.', 'ventraconnect-social-login' ),
					'cta_label'   => __( 'Upgrade to Pro', 'ventraconnect-social-login' ),
					'cta_url'     => \VentraConnect\SocialLogin\Pro_Gates::upsell_link( 'integrations-community' ),
				],
				'integrations'    => [
					'woo_memberships' => [
						'name'        => __( 'WooCommerce Memberships', 'ventraconnect-social-login' ),
						'description' => __( 'Offer one-click access to restricted WooCommerce content and member areas.', 'ventraconnect-social-login' ),
						'places'      => [
							'restricted' => __( 'Restricted content notice', 'ventraconnect-social-login' ),
							'my_account' => __( 'Member dashboard', 'ventraconnect-social-login' ),
						],
					],
					'pmpro' => [
						'name'        => __( 'Paid Memberships Pro', 'ventraconnect-social-login' ),
						'description' => __( 'Add login buttons to PMPro checkout, login, and profile templates.', 'ventraconnect-social-login' ),
						'places'      => [
							'login'    => __( 'Login form', 'ventraconnect-social-login' ),
							'checkout' => __( 'Register form', 'ventraconnect-social-login' ),
						],
					],
					'pms' => [
						'name'        => __( 'Paid Membership Subscriptions', 'ventraconnect-social-login' ),
						'description' => __( 'Add social login and passwordless access to Paid Membership Subscriptions login, registration, and account pages.', 'ventraconnect-social-login' ),
						'places'      => [],
					],
					'ultimate_member' => [
						'name'        => __( 'Ultimate Member', 'ventraconnect-social-login' ),
						'description' => __( 'Surface social login on Ultimate Member registration and login forms.', 'ventraconnect-social-login' ),
						'places'      => [
							'login'    => __( 'Login form', 'ventraconnect-social-login' ),
							'register' => __( 'Register form', 'ventraconnect-social-login' ),
						],
					],
					'buddypress' => [
						'name'        => __( 'BuddyPress', 'ventraconnect-social-login' ),
						'description' => __( 'Speed up BuddyPress community signups and profile access.', 'ventraconnect-social-login' ),
						'places'      => [
							'login'    => __( 'Login form', 'ventraconnect-social-login' ),
							'register' => __( 'Register form', 'ventraconnect-social-login' ),
						],
					],
					'peepso' => [
						'name'        => __( 'PeepSo', 'ventraconnect-social-login' ),
						'description' => __( 'Bring instant social sign-on to PeepSo community experiences.', 'ventraconnect-social-login' ),
						'places'      => [
							'login'    => __( 'Login form', 'ventraconnect-social-login' ),
							'register' => __( 'Register form', 'ventraconnect-social-login' ),
						],
					],
				],
			],
			'courses_lms' => [
				'title'           => __( 'Courses & LMS', 'ventraconnect-social-login' ),
				'description'     => __( 'Let students join and learn without password friction.', 'ventraconnect-social-login' ),
				'option_group'    => 'ventraconnect_sl_integrations_lms',
				'tab_slug'        => 'courses_lms',
				'post_login_note' => __( 'Choose where learners land after a successful social login.', 'ventraconnect-social-login' ),
				'upsell'          => [
					'title'       => __( 'Unlock Courses & LMS Integrations', 'ventraconnect-social-login' ),
					'description' => __( 'Use social login on checkout, enrollment, and dashboards across your favorite learning platforms.', 'ventraconnect-social-login' ),
					'cta_label'   => __( 'Upgrade to Pro', 'ventraconnect-social-login' ),
					'cta_url'     => \VentraConnect\SocialLogin\Pro_Gates::upsell_link( 'integrations-courses' ),
				],
				'integrations'    => [
					'learndash' => [
						'name'        => __( 'LearnDash', 'ventraconnect-social-login' ),
						'description' => __( 'Simplify enrollment and course access with LearnDash-ready social login buttons.', 'ventraconnect-social-login' ),
						'places'      => [
							'login'    => __( 'Login form', 'ventraconnect-social-login' ),
							'register' => __( 'Register form', 'ventraconnect-social-login' ),
						],
					],
					'tutor_lms' => [
						'name'        => __( 'Tutor LMS', 'ventraconnect-social-login' ),
						'description' => __( 'Add social login and passwordless access to Tutor LMS login, student registration, instructor registration, and dashboard pages.', 'ventraconnect-social-login' ),
						'places'      => [
							'login'               => __( 'Login form', 'ventraconnect-social-login' ),
							'student_register'    => __( 'Student registration form', 'ventraconnect-social-login' ),
							'instructor_register' => __( 'Instructor registration form', 'ventraconnect-social-login' ),
						],
					],
					'lifterlms' => [
						'name'        => __( 'LifterLMS', 'ventraconnect-social-login' ),
						'description' => __( 'Place buttons on LifterLMS enrollment, account, and student dashboards.', 'ventraconnect-social-login' ),
						'places'      => [
							'login'    => __( 'Login form', 'ventraconnect-social-login' ),
							'register' => __( 'Register form', 'ventraconnect-social-login' ),
							'account'  => __( 'My Account area', 'ventraconnect-social-login' ),
						],
					],
				],
			],
		];
	}
}
