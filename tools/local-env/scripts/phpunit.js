const dotenv       = require( 'dotenv' );
const dotenvExpand = require( 'dotenv-expand' );
const { execSync } = require( 'child_process' );
const local_env_utils = require( './utils' );

dotenvExpand.expand( dotenv.config() );

const composeFiles = local_env_utils.get_compose_files();

// Get the arguments passed to the script
const args = process.argv.slice(2);

// Check if the --phpunit11 flag is present
const usePhpunit11 = args.includes('--phpunit11');

// Remove the --phpunit11 flag from the arguments if it exists
const filteredArgs = args.filter(arg => arg !== '--phpunit11');

// Determine the command to run
let command = 'docker compose ' + composeFiles + ' run --rm php ./vendor/bin/phpunit';

// Add the phpunit11 directory if the flag is present
if (usePhpunit11) {
    command += ' tests/phpunit11';
}

// Add any remaining arguments
if (filteredArgs.length > 0) {
    command += ' ' + filteredArgs.join(' ');
}

// This try-catch prevents the superfluous Node.js debugging information from being shown if the command fails.
try {
    // Execute the command
    execSync(command, { stdio: 'inherit' });
} catch (error) {
    process.exit(1);
}
