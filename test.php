<?php
	require_once("connection/tetraservice_configurator_api.php");
	$api=new ConfiguratorAPI("3SO6G63et7Mz1KnE05T2z9umXeEjn5fW", 'HYDROMEC');
	// Definizione della configurazione dell'assieme
	$configuration=array(
	    'fields' => array(
	        'Input style' => 'M',
	        'Size' => 'X22S',
	        'Mounting' => 'C',
	        'Ratio' => '4,83',
	        'Output Shaft' => 'A',
	        'Type' => 'FB',
	        'Output Flange' => 'N',
	        'Input Type' => 'B',
	        'Terminal Box Position' => 'A',
	        'Mounting Position' => 'B6'
	    )
	);
	// Richiesta di generazione dell'assieme
	$assembly=null;
	try{
	    $response=$api->createAssembly('X', $configuration);
	} catch(Exception $e){
	    die("\n\nException: ".$e->getMessage());
	}
	if($response->success){
	    $assembly=$response->getContent();
	}else{
	    echo("\n\nErrors:\n" . implode('\n', $response->getErrors()));
	    die();
	}
	// Se l'assieme è già  stato generato è possibile visualizzare l'anteprima in un frame
	$preview_frame_url='';
	if($assembly['status']==10){
	    $preview_frame_url=$assembly['preview_frame_url'];
	}else{
	    // In caso contrario, richiedere lo stato dell'assieme nuovamente fino a che non è uguale a 10 (generato) o -1 (errore,
	    // probabilmente non manifold)
	    $attempts=0;
	    while($attempts < 10){
	        try{
	            $response=$api->getAssembly($assembly['hash']);
	        } catch(Exception $e){
	            die("\n\nException: " . $e->getMessage());
	        }
	        if($response->success){
	            $assembly=$response->getContent();
	            if($assembly['status']==10){
	                $preview_frame_url=$assembly['preview_frame_url'];
	                break;
	            }else if($assembly['status'] == -1){
	                break;
	            }else{
	                $attempts++;
	                sleep(5);
	                continue;
	            }
	        }else{
	            echo("\n\nErrors:\n" . implode('\n', $response->getErrors()));
	            die();
	        }
	    }
	    if($preview_frame_url == ''){
	        echo("\n\nError...");
	        die();
	    }
	}
//	echo("\n\nPreview frame URL: ".$preview_frame_url."<br>");
	// Richiesta di conversione in STEP dell'assieme
	try{
	    $response=$api->convertAssembly($assembly['hash'], '3D', 'STEP');
	} catch(Exception $e){
	    die("\n\nException: " . $e->getMessage());
	}
	$download_url='';
	if($response->success){
	    $assembly=$response->getContent();
	    // Se la conversione è già disponibile è possibile leggere direttamente la URL del modello
	    if($assembly['status']=='10'){
	        $download_url=$assembly['output_url'];
	    }else{
	        // altrimenti ripetiamo la richiesta di conversione sino a che lo stato non è uguale a 10 o -1
	        $attempts=0;
	        while($attempts < 10){
	            try{
	                $response=$api->convertAssembly($assembly['hash'], '3D', 'STEP');
	            } catch(Exception $e){
	                die("\n\nException: ".$e->getMessage ());
	            }
	            if($response->success){
	                $conversion=$response->getContent();
	                if($conversion['status']==10){
	                    $download_url=$conversion['output_url'];
	                    break;
	                }else if($assembly['status'] == -1){
	                    break;
	                }else{
	                    $attempts++;
	                    sleep(5);
	                    continue;
	                }
	            }else{
	                echo("\n\nErrors:\n" . implode('\n', $response->getErrors()));
	                die();
	            }
	        }
	    }
	}else{
	    echo("\n\nErrors:\n" . implode('\n', $response->getErrors()));
	    die();
	}
	if($download_url == ''){
	    echo("\n\nError...");
	    die();
	}
//	echo("\n\nDownload URL: " . $download_url);
/////////////////////////////////////////////////////////////////////////////////////////////////
	$url=$preview_frame_url;
//	echo$download_url;
/*
	$curl=curl_init($url);
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
	$result=curl_exec($curl);
	echo $result;
*/
	echo'<div>
<!--  INIZIO CONTENITORE DEL SERVIZIO LISTINO  -->
		<iframe src="'.$url.'" width="100%" height="100%" frameborder="0">
			<span class="testo">
				Sorry, your browser does not support inline frames!!
			</span>
		</iframe>
<!--  FINE CONTENITORE DEL SERVIZIO LISTINO  -->
	</div>';
?>