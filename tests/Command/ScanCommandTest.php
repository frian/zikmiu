<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ScanCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        // -- boot app
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        // -- get command tester
        $command = $application->find('zik:scan');
        $commandTester = new CommandTester($command);

        // -- test command error : music folder not found
        $filesystem = new Filesystem();
        $music_orig_path = Path::normalize(getcwd().'/public/music/');
        $music_test_path = Path::normalize(getcwd().'/public/music_renamed_for_tests/');

        try {
            $filesystem->rename( $music_orig_path, $music_test_path );
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while moving directory at ".$exception->getPath();
        }

        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('not found', $output);

        try {
            $filesystem->rename( $music_test_path, $music_orig_path );
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while moving directory at ".$exception->getPath();
        }

        // -- test command warning : already running
        $lockFile = Path::normalize(getcwd().'/public/zikmiu.lock');
        $filesystem->touch($lockFile);

        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('already running', $output);

        $filesystem->remove($lockFile);

        // -- test command
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Scanning', $output);
        $this->assertStringContainsString('░', $output);
        $this->assertStringContainsString('no audio file found', $output);

        // -- test command with -v
        $commandTester->execute([], [ 'verbosity' => OutputInterface::VERBOSITY_VERBOSE] );
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Clearing tables', $output);
        $this->assertStringContainsString('Scanning', $output);
        $this->assertStringContainsString('░', $output);
        // $this->assertStringContainsString('Loading db', $output); ## TODO : move to advanced test
        $this->assertStringContainsString('Summary', $output);
        $this->assertStringContainsString('analysed 0 files', $output);
        $this->assertStringContainsString('no audio file found', $output);

        // -- test command with -vv
        $commandTester->execute([], [ 'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE] );
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Clearing tables', $output);
        $this->assertStringContainsString('Scanning', $output);
        $this->assertStringNotContainsString('░', $output);
        $this->assertStringContainsString('Summary', $output);
        $this->assertStringContainsString('analysed 0 files', $output);
        $this->assertStringContainsString('no audio file found', $output);

        /*
         * -- Advanced tests : needs a "tests/Command/testfiles" folder
         * --   containing :
         * --     - a "ok" folder containing normal audio files
         * --     - a "bugs" folder containing audio files with missing tags
         */
        // -- check if the folders exist
        $testFilesPath = Path::normalize(getcwd().'/tests/Command/testfiles/');
        $okFolder = $testFilesPath . 'ok';
        $bugFolder = $testFilesPath . 'bugs';
        $symlink = Path::normalize($music_orig_path.'test');

        if (is_dir($okFolder) && is_dir($bugFolder) ) {

            /*
             *  - test with ok files
             */
            $filesystem->symlink($okFolder, $symlink);

            // -- test command
            try {
                $commandTester->execute([]);
                $commandTester->assertCommandIsSuccessful();
                $output = $commandTester->getDisplay();
                $this->assertStringContainsString('Scanning', $output);
                $this->assertStringContainsString('░', $output);
                $this->assertStringNotContainsString('no audio file found', $output);
            } catch (\Exception $e) {
                print $e->getMessage();
            }

            // -- test command with -v
            try {
                $commandTester->execute([], [ 'verbosity' => OutputInterface::VERBOSITY_VERBOSE] );
                $commandTester->assertCommandIsSuccessful();
                $output = $commandTester->getDisplay();
                $this->assertStringContainsString('Clearing tables', $output);
                $this->assertStringContainsString('Scanning', $output);
                $this->assertStringContainsString('░', $output);
                $this->assertStringContainsString('Loading db', $output);
                $this->assertStringContainsString('Summary', $output);
                $this->assertStringNotContainsString('analysed 0 files', $output);
                $this->assertStringNotContainsString('no audio file found', $output);
            } catch (\Exception $e) {
                print $e->getMessage();
            }

            // -- test command with -vv
            try {
                $commandTester->execute([], [ 'verbosity' => 128] );
                $commandTester->assertCommandIsSuccessful();
                $output = $commandTester->getDisplay();
                $this->assertStringContainsString('Clearing tables', $output);
                $this->assertStringContainsString('Scanning', $output);
                $this->assertStringNotContainsString('░', $output);
                $this->assertStringContainsString('added album', $output);
                $this->assertStringContainsString('Loading db', $output);
                $this->assertStringContainsString('Summary', $output);
                $this->assertStringNotContainsString('analysed 0 files', $output);
                $this->assertStringNotContainsString('no audio file found', $output);
            } catch (\Exception $e) {
                print $e->getMessage();
            }

            $filesystem->remove($symlink);

            /*
             *  - test with bugs files
             */
            $filesystem->symlink($bugFolder, $symlink);

            // -- test command
            try {
                $commandTester->execute([]);
                $commandTester->assertCommandIsSuccessful();
                $output = $commandTester->getDisplay();
                $this->assertStringContainsString('Scanning', $output);
                $this->assertStringContainsString('░', $output);
                $this->assertStringNotContainsString('no audio file found', $output);
                $this->assertStringContainsString('[WARNING] some files have missing tags', $output);
                $this->assertStringContainsString('check public/zikmiu.log or run with -vv', $output);
            } catch (\Exception $e) {
                print $e->getMessage();
            }

            // -- test command with -v
            try {
                $commandTester->execute([], [ 'verbosity' => OutputInterface::VERBOSITY_VERBOSE] );
                $commandTester->assertCommandIsSuccessful();
                $output = $commandTester->getDisplay();
                $this->assertStringContainsString('Clearing tables', $output);
                $this->assertStringContainsString('Scanning', $output);
                $this->assertStringContainsString('░', $output);
                $this->assertStringContainsString('[WARNING] some files have missing tags', $output);
                $this->assertStringContainsString('check public/zikmiu.log or run with -vv', $output);
                $this->assertStringContainsString('Loading db', $output);
                $this->assertStringContainsString('Summary', $output);
                $this->assertStringNotContainsString('analysed 0 files', $output);
                $this->assertStringNotContainsString('no audio file found', $output);
            } catch (\Exception $e) {
                print $e->getMessage();
            }

            // -- test command with -vv
            try {
                $commandTester->execute([], [ 'verbosity' => 128] );
                $commandTester->assertCommandIsSuccessful();
                $output = $commandTester->getDisplay();
                $this->assertStringContainsString('Clearing tables', $output);
                $this->assertStringContainsString('Scanning', $output);
                $this->assertStringNotContainsString('░', $output);
                $this->assertStringContainsString('added album', $output);
                $this->assertStringContainsString('[WARNING]', $output);
                $this->assertStringContainsString('[ERROR]', $output);
                $this->assertStringContainsString('SKIPPING FILE', $output);
                $this->assertStringContainsString('Loading db', $output);
                $this->assertStringContainsString('Summary', $output);
                $this->assertStringNotContainsString('analysed 0 files', $output);
                $this->assertStringNotContainsString('no audio file found', $output);
            } catch (\Exception $e) {
                print $e->getMessage();
            }

            $filesystem->remove($symlink);
        }
    }
}