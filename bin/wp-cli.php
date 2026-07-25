<?php
/**
 * WP-CLI Commands for ON Toolkit
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class ONToolkit_CLI_Command extends WP_CLI_Command {

	/**
	 * Audit database overhead and perform safe space recovery.
	 *
	 * ## OPTIONS
	 *
	 * [--target=<target>]
	 * : Target clean area: revisions, trash_posts, expired_transients, orphan_postmeta, or all.
	 *
	 * [--dry-run]
	 * : Perform a preview audit without deleting data.
	 *
	 * ## EXAMPLES
	 *
	 *     wp on-toolkit clean-db --target=expired_transients
	 *     wp on-toolkit clean-db --dry-run
	 */
	public function clean_db( $args, $assoc_args ) {
		$target  = $assoc_args['target'] ?? 'all';
		$dry_run = isset( $assoc_args['dry-run'] );

		$service = new \ONToolkit\Modules\DbCleaner\Services\CleanUpService();

		if ( $dry_run ) {
			WP_CLI::log( 'Running ON Toolkit Database Cleanup Audit (Dry-Run)...' );
			$summary = $service->getAuditSummary();
			WP_CLI::line( 'Revisions: ' . $summary['revisions']['count'] . ' (' . $summary['revisions']['formatted_size'] . ')' );
			WP_CLI::line( 'Trash Posts: ' . $summary['trash_posts']['count'] . ' (' . $summary['trash_posts']['formatted_size'] . ')' );
			WP_CLI::line( 'Expired Transients: ' . $summary['expired_transients']['count'] );
			WP_CLI::line( 'Orphan Postmeta: ' . $summary['orphan_postmeta']['count'] );
			WP_CLI::success( 'Total Recoverable Space: ' . $summary['total_formatted_size'] );
			return;
		}

		$targets = ( $target === 'all' ) ? array( 'revisions', 'trash_posts', 'expired_transients', 'orphan_postmeta' ) : array( $target );

		foreach ( $targets as $t ) {
			WP_CLI::log( "Cleaning target: {$t}..." );
			$res = $service->cleanTarget( $t, false );
			WP_CLI::success( "Cleaned {$t}: " . ( $res['deleted_count'] ?? 0 ) . ' items removed.' );
		}
	}

	/**
	 * Audit Media Library for unused files and missing ALT text.
	 *
	 * ## EXAMPLES
	 *
	 *     wp on-toolkit audit-media
	 */
	public function audit_media( $args, $assoc_args ) {
		$detector = new \ONToolkit\Modules\MediaInspector\Services\UsageDetector();
		WP_CLI::log( 'Auditing Media Library...' );
		$result = $detector->auditMedia( 50, 0, 'all' );

		$summary = $result['summary'];
		WP_CLI::line( 'Total Audited: ' . $summary['total_audited'] );
		WP_CLI::line( 'Unused Images: ' . $summary['unused_count'] );
		WP_CLI::line( 'Missing ALT Text: ' . $summary['missing_alt_count'] );
		WP_CLI::line( 'Oversized (>2MB): ' . $summary['oversized_count'] );
		WP_CLI::success( 'Media Library Audit Complete.' );
	}
}

WP_CLI::add_command( 'on-toolkit', 'ONToolkit_CLI_Command' );
