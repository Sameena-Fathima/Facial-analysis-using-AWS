<!DOCTYPE html>
<html>
<head>
<style>
.up1{
        margin-top:20px;
        margin-left:50px;
}
.up2{
        margin-top:-20px;
        margin-left:750px;
}
.img1 {
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-top:40px;
  margin-left:100px;
  width: 500px;
  height:350px;
}
.img2 {
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-top:-450px;
  margin-left:750px;
  width: 500px;
  height:350px;
}
.part{
border:solid black 1px;
height:300px;
width:400px;
margin-left:450px;
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
	$file1=$_FILES['fileToUpload1'];
    $fileName=$_FILES['fileToUpload1']['name'];
	$fileerr=$_FILES['fileToUpload1']['error'];
	$filetemp1=$_FILES['fileToUpload1']['tmp_name'];
	$fileext=explode('.',$fileName);
	$file2=$_FILES['fileToUpload2'];
	$filetemp2=$_FILES['fileToUpload2']['tmp_name'];
	if($fileerr==0)
	{

		$newname1="source.jpg";
		$dest1=$newname1;
		$newname2="target.jpg";
		$dest2=$newname2;
		move_uploaded_file($filetemp1,$dest1);
		move_uploaded_file($filetemp2,$dest2);
		$img1='source.jpg';
        echo '<img src="'.$img1.'" class="img1">';
        $img2='target.jpg';
        echo '<img src="'.$img2.'" class="img2">';
		$bucket = 'aws-facedet';
		$sourceimg = 'source.jpg';
		$destimg = 'target.jpg';
		$s3 = new S3Client([
			'region' => 'us-east-2',
			'version' => '2006-03-01',
			'signature' => 'v4'
			]);

        try 
		{
			$result1 = $s3->putObject([
			'Bucket' => $bucket,
			'Key' => $sourceimg,
			'SourceFile' => __DIR__. "/$sourceimg",
			'ACL' => 'public-read-write']);
			$result2 = $s3->putObject([
				'Bucket'                => $bucket,
				'Key'                   => $destimg,
				'SourceFile'    => __DIR__. "/$destimg",
				'ACL'                   => 'public-read-write']);

			$imageUrl = $result1['ObjectURL'];
			if($imageUrl) 
			{

				$rekognition = new RekognitionClient([
				'region'        => 'us-east-2',
				'version'       => 'latest',]);
				$result = $rekognition->detectFaces([
                        'Attributes'    => ['ALL'],
                        'Image' => [
                                'S3Object' => [
                                        'Bucket' => $bucket,
                                        'Name'  =>      $sourceimg,
                                        'Key'   =>      $sourceimg,
                                ],
                        ],
                ]);
				
				if(count($result["FaceDetails"])==1){
					$result = $rekognition->compareFaces([
		     	'QualityFilter' => 'MEDIUM',
				'SimilarityThreshold' => 90,
				'SourceImage' => [ 'Bytes'=>file_get_contents("source.jpg")],
				'TargetImage' => [ 'Bytes'=>file_get_contents("target.jpg")]
				]);
				if(count($result['FaceMatches'])>0)
				{
					echo "</br></br><center>MATCHING FACE FOUND</center>";
				}
				else
				{
					echo "</br></br><center>NO MATCHING FACE FOUND</center>";
				}
				$result=$rekognition->recognizeCelebrities([
				'Image'=>[
				'Bytes'=>file_get_contents("source.jpg"),
				],
				'MaxLabels'=>10,
				'MinConfidence'=>20,
				]);
			    if(count($result['CelebrityFaces'])>0)
			    {
					echo("</br></br><center>The uploaded source image is a celebrity:".$result['CelebrityFaces'][0]['Name']."</center>");
			    }
			}
			else
			{
				echo "</br></br><center>More than one face detected in the source image!!!</center>";
			}
			}
		}
		catch (Exception $e) 
		{
			echo $e->getMessage() . PHP_EOL;
		}

	}
}
?>

<form action="" method="post" enctype="multipart/form-data">
<h3>Upload an image of a person as source image and an image having one or more persons as test image to test for a matching face in the test image and also to recognize the celebrity name if the person in the source image is a celebrity.
<div class="up1">
    Source Image:
    <input type="file" name="fileToUpload1" id="fileToUpload1"></br>
</div>
<div class="up2">
    Test Image:
    <input type="file" name="fileToUpload2" id="fileToUpload2"></br></div>
    <center><input type="submit" value="Upload Images" name="submit"></center>
</form>
</body>
</html>




