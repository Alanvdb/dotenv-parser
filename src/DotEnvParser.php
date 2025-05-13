<?php declare(strict_types=1);

namespace AlanVdb\Server;

use AlanVdb\Server\Definition\DotEnvParserInterface;
use AlanVdb\Server\Exception\InvalidRootDirectoryProvided;
use AlanVdb\Server\Exception\CannotParseDotEnv;

class DotEnvParser implements DotEnvParserInterface
{
    /**
     * @var string Path to the root directory
     */
    private string $root;

    /**
     * Constructor to set the root directory
     * 
     * @param string $root Path to the root directory
     * @throws InvalidRootDirectoryProvided If the root directory is invalid
     */
    public function __construct(string $root)
    {
        // Validate the root directory
        $realPath = realpath($root);
        
        if ($realPath === false || !is_dir($realPath)) {
            throw new InvalidRootDirectoryProvided(
                sprintf('The provided root directory "%s" is not a valid directory.', $root)
            );
        }
        
        $this->root = rtrim($realPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Parse .env file and return environment variables
     * 
     * @return array Parsed environment variables
     * @throws CannotParseDotEnv If the .env file cannot be parsed
     */
    public function parse(): array
    {
        $envFile = $this->root . DIRECTORY_SEPARATOR . '.env';
        
        // Check if .env file exists
        if (!file_exists($envFile)) {
            throw new CannotParseDotEnv('No .env file found in the specified root directory.');
        }
        
        // Try to read and parse the .env file
        if (!is_readable($envFile)) {
            throw new CannotParseDotEnv('Unable to read .env file: insufficient permissions.');
        }
        
        try {
            $envContents = file_get_contents($envFile);
            
            if ($envContents === false) {
                throw new CannotParseDotEnv('Unable to read .env file.');
            }
            
            return array_merge(
                $this->parseEnvContents($envContents),
                ['ROOT_PATH' => $this->root]
            );
        } catch (\Throwable $e) {
            if (!$e instanceof CannotParseDotEnv) {
                throw new CannotParseDotEnv(
                    sprintf('Error parsing .env file: %s', $e->getMessage()),
                    0,
                    $e
                );
            }
            throw $e;
        }
    }

    /**
     * Parse the contents of the .env file
     * 
     * @param string $contents Contents of the .env file
     * @return array Parsed environment variables
     */
    private function parseEnvContents(string $contents): array
    {
        $envVars = [];
        $lines = preg_split('/\R/', $contents);
        
        foreach ($lines as $line) {
            // Trim whitespace
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            // Remove comments from the line (if any)
            $line = preg_replace('/#.*$/', '', $line);
            $line = trim($line);
            
            // Split into key and value
            $parts = preg_split('/=/', $line, 2);
            
            if (count($parts) !== 2) {
                continue; // Skip malformed lines
            }
            
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Remove quotes if present
            $value = $this->stripQuotes($value);
            
            $envVars[$key] = $value;
        }
        
        return $envVars;
    }

    /**
     * Remove quotes from a value
     * 
     * @param string $value Value to strip quotes from
     * @return string Unquoted value
     */
    private function stripQuotes(string $value): string
    {
        // Remove surrounding single quotes
        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return substr($value, 1, -1);
        }
        
        // Remove surrounding double quotes
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
}
