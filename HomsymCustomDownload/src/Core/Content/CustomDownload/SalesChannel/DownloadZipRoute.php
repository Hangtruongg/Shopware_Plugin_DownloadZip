<?php declare(strict_types=1);

namespace Download\Core\Content\CustomDownload\SalesChannel;

use Download\Core\Content\CustomDownload\SalesChannel\AbstractDownloadZipRoute;
use Shopware\Core\Checkout\Customer\CustomerException;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItemDownload\OrderLineItemDownloadEntity;
use Shopware\Core\Content\Media\File\DownloadResponseGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use ZipArchive;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Media\MediaEntity;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use getID3;
use getid3_writetags;
require_once __DIR__ . '/getID3/getid3.php';
require_once __DIR__ . '/getID3/write.php';

/**
 * This is the original class, which is the same as the test class 'DownloadZipRoute_Test'
 * However this class will not contain all the debug code and commented code
 */

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class DownloadZipRoute extends AbstractDownloadZipRoute
{
    private KernelInterface $kernel;
    private LoggerInterface $logger;
    private Filesystem $filesystem;

    public function __construct(
        private readonly EntityRepository $downloadRepository,
        private readonly EntityRepository $orderRepository,
        private readonly DownloadResponseGenerator $downloadResponseGenerator,
        KernelInterface $kernel,
        LoggerInterface $logger
    )
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
        $this->filesystem = new Filesystem();
    }

    public function getDecorated(): AbstractDownloadZipRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/account/order/download/zip/{orderId}/{downloadId}', name: 'store-api.account.order.download.zip', methods: ['GET'], defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true])]
    public function load(Request $request, SalesChannelContext $context): Response
    {
        $logMessage = "[" . date("Y-m-d H:i:s") . "]";
        $customer = $context->getCustomer();
        $orderId = $request->get('orderId', false);
        $downloadId = $request->get('downloadId', false);

        if (!$customer) {
            throw CustomerException::customerNotLoggedIn();
        }


        if ($downloadId === false || $orderId === false) {
            throw RoutingException::missingRequestParameter(!$downloadId ? 'downloadId' : 'orderId');
        }


        // Criteria to get the download
        $criteria = new Criteria([$downloadId]);
        $criteria->addAssociation('media');
        $criteria->addFilter(new MultiFilter(
            MultiFilter::CONNECTION_AND,
            [
                new EqualsFilter('orderLineItem.order.id', $orderId),
                new EqualsFilter('orderLineItem.order.orderCustomer.customerId', $customer->getId()),
                new EqualsFilter('accessGranted', true)
            ]
        ));

        $downloads = $this->downloadRepository->search($criteria, $context->getContext());

        if ($downloads->count() === 0) {
            throw new \RuntimeException("No downloads found for this order.");
        }

        // Criteria to get the order_number
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('orderCustomer'); // Include related data if needed

        $order = $this->orderRepository->search($criteria, $context->getContext())->first();

        if (!$order) {
            throw new \RuntimeException('Order not found.');
        }

        // get order_number <$order>
        $orderNumber = $order->getOrderNumber();
        // get the customerNumber
        $customerNumber = $customer->getCustomerNumber();
        $customerFirstName = $customer->getFirstName();
        $customerLastName = $customer->getLastName();

       $existingZipPaths = [];
       $zipDirectory = $this->getESDZipFilesPath();//path to the zip-files

        // Loop through all the downloads and add the compatible medias from the subfolders
        foreach ($downloads as $download) {
            $media = $download->getMedia();
            if ($media) {

                // Extract the subfolder name
                $subfolderName = $this->getSubfolderName($media->getPath());
                $articleNumber = $this->getSubfolderName($media->getPath());

                // Define the expected ZIP file path
                $existingZipPath = $zipDirectory . $articleNumber . "_" . $customerNumber . "_" . $orderNumber . ".zip";

                if(!in_array($existingZipPath, $existingZipPaths) && file_exists($existingZipPath)) {
                    $zipFiles[] = $existingZipPath;
                    $existingZipPaths[] = $existingZipPath;
                    file_put_contents(__DIR__ . '/custom_debug_dev.log',"\n$logMessage The Zip already exist under zip-files.\n", FILE_APPEND);
                } else {
                    // first, create the temporary package for this article
                    $tmpFolder = $this->createTmpPackage($articleNumber, $customerNumber, $orderNumber);

                    if ($tmpFolder && file_exists($tmpFolder)) {
                        $zipFiles[] = $tmpFolder;
                    } else {
                        throw new \RuntimeException("Failed to create ZIP package: " . $tmpFolder);
                    }

                }
            }
        }

        $finalZipFileName = 'HomSym_'. $articleNumber . '_' . $customerNumber . '_' . $orderNumber . '.zip';
        $zipFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $finalZipFileName;
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Failed to create final ZIP file.");
        }

        // Extract and add files from each ZIP
        foreach ($zipFiles as $zipFile) {
            $tempZip = new ZipArchive();
            if ($tempZip->open($zipFile) === TRUE) {
                for ($i = 0; $i < $tempZip->numFiles; $i++) {
                    $filename = $tempZip->getNameIndex($i);
                    $stream = $tempZip->getStream($filename);
                    if ($stream) {
                        // Stream file into the new ZIP instead of loading into memory
                        $zip->addFromString($filename, stream_get_contents($stream));
                        fclose($stream);
                    } else {
                        throw new \RuntimeException("Failed to read file: " . $filename . " inside " . $zipFile);
                    }
                }
                $tempZip->close();
            } else {
                throw new \RuntimeException("Failed to read ZIP file: " . $zipFile);
            }
        }

        $zip->close();

        file_put_contents(__DIR__ . '/custom_debug.log',"\n$logMessage $customerFirstName $customerLastName ($customerNumber) downloaded $articleNumber with Order Number $orderNumber .\n", FILE_APPEND);

        // Create the response manually
        $response = new StreamedResponse(function () use ($zipFilePath) {
            readfile($zipFilePath);
        });

        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($zipFilePath) . '"');
        $response->headers->set('Content-Length', (string) filesize($zipFilePath));

        // Return the response
        return $response;

    }

    /**
     * Extracts the subfolder name from the given media path.
     */
    private function getSubfolderName(string $mediaPath): string
    {
        $pathParts = explode('/', trim($mediaPath, '/'));
        if (count($pathParts) >= 2) {
            return $pathParts[count($pathParts) - 2]; // Return the 2nd last part
        }

        throw new \RuntimeException("Invalid media path structure: " . $mediaPath);
    }

    /**
     * Add files to ZIP archive
     * same as the function folderToZip() from PHP:ZipArchive
     */
    private function addFilesToZip(ZipArchive $zip, string $folderPath, string $zipFolderName)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $relativePath = $zipFolderName . '/' . $file->getFilename();
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    // createTmpPackage and Encryption
    public function createTmpPackage($articleNumber, $customerNumber, $orderNumber)
    {
        $logMessage = "[" . date("Y-m-d H:i:s") . "]";
        $tmp_folder =$articleNumber . "_" . $customerNumber."_".$orderNumber;
        $files_folder = $this->getESDFilesPath();
        $files_folder_zip = $this->getESDZipFilesPath();
        $tmp_folder_path = $files_folder_zip.$tmp_folder;

        $EncryptedCode = $this->EncryptComment($orderNumber, $customerNumber);

        // Check if the folder already exists
        if(!$this->filesystem->exists($tmp_folder_path)){
            $this->filesystem->mkdir($tmp_folder_path);
        }

        //Copy of all the file in the temp folder
        $files_article = glob($files_folder.$articleNumber."/*", GLOB_BRACE);
        $files_general = glob($files_folder."General/*", GLOB_BRACE);
        $files = array_merge($files_article, $files_general);

        foreach($files as $file){
            //$fileExt = pathinfo($file, PATHINFO_EXTENSION);
            $fileExt = pathinfo($file)['extension'];
            $fileName = basename($file);
            $destinationPath = $tmp_folder_path . "/" . $fileName;

            if($fileExt == "mp3"){
                // If MP3, add ID3 tag
                copy($file, $destinationPath);
                $this->addID3Tag($destinationPath, $EncryptedCode); // Function addID3Tag()
            }
            else{
                copy($file, $destinationPath);
            }
        }

        //Creation of the zipfile
        $zipname = "$tmp_folder_path.zip";
        $zip = new ZipArchive();

        if ($zip->open($zipname, ZipArchive::CREATE)!==TRUE) {
            file_put_contents(__DIR__ . '/custom_debug_dev.log', "\n$logMessage Impossible to create the zip file\n", FILE_APPEND);
            exit;
            //return '';
        }

        //Files of the temp folder are stored in the zipfile_add files to Zip
        $files_tmp = glob("$tmp_folder_path/*", GLOB_BRACE);
        foreach($files_tmp as $file){
            $fileName = basename($file);
            $zip->setCompressionName($fileName, ZipArchive::CM_DEFLATE);
            $zip->addFile($file, $fileName);
        }

        $zip->close();

        //Delete of the temp folder| This is temporary folder, can keep (for debugging) but should be deleted

        /**
         * array_map('unlink', glob("$tmp_folder_path/*"));
        $this->filesystem->remove($tmp_folder_path);
        file_put_contents(__DIR__ . '/custom_debug_dev.log', "\n$logMessage The temporary file is deleted.\n", FILE_APPEND);
        */

        return $zipname;
    }

    /**
     * @param string $filePath
     * @param string $EncryptedCode
     * @return void
     * This is the function to add id3tag to the .mp3 file.
     * Using library GetID3 from : https://github.com/JamesHeinrich/getID3/tree/master
     * Only some files from the library be used in this plugin (Check under Folder 'GetID3')
     */
    private function addID3Tag(string $filePath, string $EncryptedCode): void
    {
        $logMessage = "[" . date("Y-m-d H:i:s") . "]";

        $tagWriter = new getid3_writetags();
        $tagWriter->filename =  $filePath ; // need to make sure this filePath is correct
        $tagWriter->tagformats = ['id3v2.3']; // this tagformats is important
        $tagWriter->overwrite_tags = true;
        $tagWriter->remove_other_tags = false;
        $tagWriter->tag_encoding = 'UTF-8';

        // Informations(Tags) will be added to the .mp3
        $tagWriter->tag_data = [
            'artist' => ['test Artist'],
            'album' => ['test Album'],
            'year' => ['2025'],
            'publisher' => [$EncryptedCode],
        ];


        if (!$tagWriter->WriteTags()) {
            file_put_contents(__DIR__ . '/custom_debug.log', "\n$logMessage Failed to add ID3 tag to\n" . basename($filePath), FILE_APPEND);
            $this->logger->error("Failed to write ID3 tags to $filePath. Errors: " . print_r($tagWriter->errors, true));
        }
    }

    private function EncryptComment($orderID, $customerNumber){
        //code encryption. Note we need to concatenate tre random number to obtain a 28 digit, it is not possible to create a rand(28digit) for php limitations
        $random = ((string)rand(1000000000, 9999999999) . (string)rand(1000000000, 9999999999) . (string)rand(10000000, 99999999));
        //the customer id and order id will be a 5 digit, e.g. 00049 and 04509
        //encrypt customer id
        $random[24] = $customerNumber[0];
        $random[20] = $customerNumber[1];
        $random[17] = $customerNumber[2];
        $random[5]  = $customerNumber[3];
        $random[1]  = $customerNumber[4];
        //encrypt order id
        $random[11] = $orderID[0];
        $random[3]  = $orderID[1];
        $random[25] = $orderID[2];
        $random[15] = $orderID[3];
        $random[22] = $orderID[4];

        return $random;
    }

    /**
     * Return the path to ESD base folder
     */
    public function getESDFilesPath(): string
    {
        return $this->kernel->getProjectDir() . "/files/media/ESDFolders/";
    }


    /**
     * Return the path to ESD temp zip files folder
     */
    public function getESDZipFilesPath(): string
    {
        return $this->getESDFilesPath() . "zip-files/";
    }

    /**
     * Function to delete the tmpFolder
     */
    private function deleteFolder($folderPath) {
        if (!is_dir($folderPath)) {
            return;
        }

        $files = array_diff(scandir($folderPath), array('.', '..'));

        foreach ($files as $file) {
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
            is_dir($filePath) ? $this->deleteFolder($filePath) : unlink($filePath);
        }

        rmdir($folderPath);
    }
}