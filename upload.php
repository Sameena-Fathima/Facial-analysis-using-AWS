<html>
<head>
<style>
img {
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-top: 50px;
  margin-left:450px;
  width: 500px;
  height:350px;
}

</style>
</head>
<body>
<?php
require_once(__DIR__ . '/vendor/autoload.php');

use Aws\S3\S3Client;
use Aws\Rekognition\RekognitionClient;
if(isset($_POST['submit']))
{
    $file=$_FILES['fileToUpload'];
    $fileName=$_FILES['fileToUpload']['name'];
    $fileerr=$_FILES['fileToUpload']['error'];
    $filetemp=$_FILES['fileToUpload']['tmp_name'];
    $fileext=explode('.',$fileName);

    if($fileerr==0)
    {

        $newname="sample.jpg";
        $dest=$newname;
        move_uploaded_file($filetemp,$dest);
		$img='sample.jpg';
        echo '<img src="'.$img.'">';
    }

	$bucket = 'aws-facedet';
	$keyname = 'sample.jpg';

	$s3 = new S3Client([
			'region'        => 'us-east-2',
			'version'       => '2006-03-01',
			'signature'     => 'v4'
	]);

	try {
        $result = $s3->putObject([
                'Bucket'                => $bucket,
                'Key'                   => $keyname,
                'SourceFile'    => __DIR__. "/$keyname",
                'ACL'                   => 'public-read-write'
        ]);

        $imageUrl = $result['ObjectURL'];
        if($imageUrl) {
			 $rekognition = new RekognitionClient([
                        'region'        => 'us-east-2',
                        'version'       => 'latest',
                ]);

                $result = $rekognition->detectFaces([
                        'Attributes'    => ['ALL'],
                        'Image' => [
                                'S3Object' => [
                                        'Bucket' => $bucket,
                                        'Name'  =>      $keyname,
                                        'Key'   =>      $keyname,
                                ],
                        ],
                ]);
                $mc=0;$fc=0;$eg=0;
                for($i=0;$i<sizeof($result['FaceDetails']);$i++)
                {
                        if($result['FaceDetails'][$i]['Gender']['Value']=='Female')

							$fc=$fc+1;
                        else if($result['FaceDetails'][$i]['Gender']['Value']=='Male')
                            $mc=$mc+1;
                        if($result['FaceDetails'][$i]['Eyeglasses']['Value']==true)
                            $eg=$eg+1;
                }
                echo "<table style='width:25%;margin-left:350px;'>
				<tr>
				<th>TOTAL FACES</th>
				<td>".count($result["FaceDetails"]) ."</td></tr><tr><th>MALES</th><td>".$mc."</td></tr><tr><th>FEMALES</th><td>".$fc."</td></tr><tr><th>EYEGLASSES</th><td>".$eg."</td></tr></table>";
			}
		} 
		catch (Exception $e) 
		{
			echo $e->getMessage() . PHP_EOL;
		}
}
?>
</body>
</html>
