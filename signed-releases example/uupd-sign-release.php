<?php
/**
 * UUPD 2.1 release signing helper (CLI only).
 *
 * Private key input may come from:
 *   --private-key-file=/path/to/key
 *   --private-key-env=ENV_NAME
 *   --private-key-stdin
 *
 * stdin/env modes allow deploy scripts to sign without writing the private key
 * to disk or exposing it in the process command line.
 */

if ( PHP_SAPI !== 'cli' ) {
    fwrite( STDERR, "CLI only.\n" );
    exit( 1 );
}

if ( ! function_exists( 'sodium_crypto_sign_detached' ) ) {
    fwrite( STDERR, "PHP sodium/Ed25519 support is required.\n" );
    exit( 1 );
}

$options = getopt( '', [
    'generate',
    'metadata:',
    'package:',
    'private-key-file:',
    'private-key-env:',
    'private-key-stdin',
    'public-key-file:',
    'public-key-output',
    'key-id::',
    'output:',
] );

if ( isset( $options['generate'] ) ) {
    $private_file = $options['private-key-file'] ?? '';
    $public_file  = $options['public-key-file'] ?? '';
    if ( $private_file === '' || $public_file === '' ) {
        fwrite( STDERR, "--generate requires --private-key-file and --public-key-file.\n" );
        exit( 1 );
    }

    $keypair = sodium_crypto_sign_keypair();
    $secret  = sodium_crypto_sign_secretkey( $keypair );
    $public  = sodium_crypto_sign_publickey( $keypair );

    if ( false === file_put_contents( $private_file, base64_encode( $secret ) . PHP_EOL, LOCK_EX ) ) {
        fwrite( STDERR, "Could not write private key file.\n" );
        exit( 1 );
    }
    @chmod( $private_file, 0600 );
    if ( false === file_put_contents( $public_file, base64_encode( $public ) . PHP_EOL, LOCK_EX ) ) {
        fwrite( STDERR, "Could not write public key file.\n" );
        exit( 1 );
    }

    fwrite( STDOUT, "Generated Ed25519 key pair. Keep {$private_file} secret; distribute only {$public_file}.\n" );
    exit( 0 );
}

$metadata_file = $options['metadata'] ?? '';
$package_file  = $options['package'] ?? '';
$output_file   = $options['output'] ?? '';
$key_id        = isset( $options['key-id'] ) ? trim( (string) $options['key-id'] ) : '';

foreach ( [ 'metadata' => $metadata_file, 'package' => $package_file, 'output' => $output_file ] as $name => $file ) {
    if ( $file === '' ) {
        fwrite( STDERR, "Missing --{$name}.\n" );
        exit( 1 );
    }
}

$metadata_raw = file_get_contents( $metadata_file );
if ( false === $metadata_raw ) {
    fwrite( STDERR, "Could not read metadata file.\n" );
    exit( 1 );
}

$metadata = json_decode( $metadata_raw, true );
if ( ! is_array( $metadata ) ) {
    fwrite( STDERR, "Metadata must be a JSON object.\n" );
    exit( 1 );
}

$package_hash = hash_file( 'sha256', $package_file );
if ( ! is_string( $package_hash ) ) {
    fwrite( STDERR, "Could not hash package.\n" );
    exit( 1 );
}
$metadata['package_sha256'] = strtolower( $package_hash );

$payload = json_encode( $metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
if ( false === $payload ) {
    fwrite( STDERR, "Could not encode metadata.\n" );
    exit( 1 );
}

$key_sources = 0;
$key_sources += ! empty( $options['private-key-file'] ) ? 1 : 0;
$key_sources += ! empty( $options['private-key-env'] ) ? 1 : 0;
$key_sources += isset( $options['private-key-stdin'] ) ? 1 : 0;
if ( $key_sources !== 1 ) {
    fwrite( STDERR, "Specify exactly one private-key source: --private-key-file, --private-key-env, or --private-key-stdin.\n" );
    exit( 1 );
}

$secret_b64 = '';
if ( ! empty( $options['private-key-file'] ) ) {
    $raw = @file_get_contents( (string) $options['private-key-file'] );
    if ( false === $raw ) {
        fwrite( STDERR, "Could not read private key file.\n" );
        exit( 1 );
    }
    $secret_b64 = trim( $raw );
} elseif ( ! empty( $options['private-key-env'] ) ) {
    $env_name = (string) $options['private-key-env'];
    $raw = getenv( $env_name );
    if ( false === $raw || trim( $raw ) === '' ) {
        fwrite( STDERR, "Private key environment variable '{$env_name}' is empty or unavailable.\n" );
        exit( 1 );
    }
    $secret_b64 = trim( $raw );
} else {
    $raw = stream_get_contents( STDIN );
    if ( false === $raw || trim( $raw ) === '' ) {
        fwrite( STDERR, "No private key received on stdin.\n" );
        exit( 1 );
    }
    $secret_b64 = trim( $raw );
}

$secret = base64_decode( $secret_b64, true );
if ( false === $secret || strlen( $secret ) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES ) {
    fwrite( STDERR, "Private key is invalid; expected base64 Ed25519 secret key.\n" );
    exit( 1 );
}

$public = sodium_crypto_sign_publickey_from_secretkey( $secret );
if ( isset( $options['public-key-output'] ) ) {
    fwrite( STDOUT, base64_encode( $public ) . PHP_EOL );
    sodium_memzero( $secret );
    exit( 0 );
}

$signature = sodium_crypto_sign_detached( $payload, $secret );
sodium_memzero( $secret );

/*
 * Preserve the normal UUPD metadata at the top level for backwards
 * compatibility and human readability. The exact same metadata object
 * (including package_sha256) is also encoded in `signed`; UUPD 2.1
 * verifies that immutable payload rather than trusting the duplicated
 * top-level fields.
 *
 * Older/unsigned consumers can continue reading slug/version/download_url
 * etc. and will simply ignore the additional signing fields.
 */
$manifest = $metadata;
$manifest['alg']       = 'ed25519';
$manifest['signed']    = base64_encode( $payload );
$manifest['signature'] = base64_encode( $signature );

if ( $key_id !== '' ) {
    $manifest['key_id'] = $key_id;
}

$json = json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
if ( false === file_put_contents( $output_file, $json, LOCK_EX ) ) {
    fwrite( STDERR, "Could not write output.\n" );
    exit( 1 );
}

fwrite( STDOUT, "Signed metadata written to {$output_file}; package SHA-256: {$package_hash}\n" );
