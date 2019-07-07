
<!DOCTYPE html>
<html lang="en">

<head>
    
    <script src="RecordRTC.js"></script>
    <script src="gumadapter.js"></script>
	
</head>

<body>
		<input type="button"  value="开始录音" id="start" onclick="start();"/>
		<input type="button"  value="暂停录音" id="stop_record" onclick="stop_record();"/>
		<input type="button"  value="结束录音" id="save" onclick="save();"/>
		
		<audio controls muted id="audio_test"></audio>
       
	   
		<audio id="audio111" src="uploads\222.mp3" controls="controls"></audio>
		

        <script>
            (function() 
			{
                var params = {},
                    r = /([^&=]+)=?([^&]*)/g;
				
                function d(s) {
                    return decodeURIComponent(s.replace(/\+/g, ' '));
                }
				
                var match, search = window.location.search;
                while (match = r.exec(search.substring(1))) {
                    params[d(match[1])] = d(match[2]);
					
                    if(d(match[2]) === 'true' || d(match[2]) === 'false') {
                        params[d(match[1])] = d(match[2]) === 'true' ? true : false;                
					}
                }
				
                window.params = params;
            })();
        </script>

        <script>
            
			var record_start=document.getElementById('start');
			
			var recordingPlayer = document.getElementById('audio_test');
			
            
			var commonConfig = 
			{
				onMediaCaptured: function(stream) 
				{
					record_start.stream = stream;
					if(record_start.mediaCapturedCallback) 
					{
						record_start.mediaCapturedCallback();
					}
					
					record_start.disabled = false;
				},
				
				onMediaCapturingFailed: function(error) 
				{
					if(error.name === 'PermissionDeniedError' && !!navigator.mozGetUserMedia) 
					{
						InstallTrigger.install({
							'Foo': {
								URL: 'https://addons.mozilla.org/en-US/firefox/addon/enable-screen-capturing/',
								toString: function () {
									return this.URL;
								}
							}
						});
					}
					//commonConfig.onMediaStopped();
				}
			};
			
			function start()
			{
				captureAudio(commonConfig);

				record_start.mediaCapturedCallback = function() 
				{
					record_start.recordRTC = RecordRTC(record_start.stream, {
						type: 'audio',
						bufferSize: 16384,//typeof params.bufferSize == 'undefined' ? 0 : parseInt(params.bufferSize),
						sampleRate: 44100,//typeof params.sampleRate == 'undefined' ? 44100 : parseInt(params.sampleRate),
						leftChannel: params.leftChannel || false,
						disableLogs: params.disableLogs || false,
						recorderType: webrtcDetectedBrowser === 'edge' ? StereoAudioRecorder : null
					});

					record_start.recordingEndedCallback = function(url) 
					{
						var audio = new Audio();
						audio.src = url;
						audio.controls = true;
						
						if(audio.paused) audio.play();

						audio.onended = function() {
							audio.pause();
							audio.src = URL.createObjectURL(record_start.recordRTC.blob);
						};
					};

					record_start.recordRTC.startRecording();
				};
				//setMediaContainerFormat(['WAV', 'Ogg']);
			}
			
			
            function captureAudio(config) 
			{
				var mediaConstraints={audio: true};
				var successCallback=function(audioStream) 
				{
                    recordingPlayer.srcObject = audioStream;
                    recordingPlayer.play();

                    config.onMediaCaptured(audioStream);

                    audioStream.onended = function() {
                        //config.onMediaStopped();
                    };
                };
				var errorCallback=function(error) 
				{
                    config.onMediaCapturingFailed(error);
                };
				
                navigator.mediaDevices.getUserMedia(mediaConstraints).then(successCallback).catch(errorCallback);
				
            }
			
			function stop_record()
			{
				if(document.getElementById("stop_record").value=="暂停录音")
				{
					
					record_start.recordRTC.pauseRecording();
					recordingPlayer.pause();
					document.getElementById("stop_record").value="继续录音";
				}else{
					recordingPlayer.play();
					record_start.recordRTC.resumeRecording();
					document.getElementById("stop_record").value="暂停录音";
				}
			}
            

			function save()
			{
				setTimeout(function() 
				{
					record_start.disabled = false;
					record_start.disableStateWaiting = false;
				}, 2 * 1000);
				
				
				
				record_start.recordRTC.stopRecording(function(url) 
				{
					//record_start.recordingEndedCallback(url);
					record_start.stream.stop();
					record_start.stream = null;
					
					alert(888);
						
					uploadToServer(record_start.recordRTC, function(){});
					
				});
			}
            

            //var listOfFilesUploaded = [];

            function uploadToServer(recordRTC, callback) 
			{
                //将音频文件和对应得题目图片保存在同一个文件夹
				recordingPlayer.pause();
				
				var blob = recordRTC instanceof Blob ? recordRTC : recordRTC.blob;
                var fileType = blob.type.split('/')[0] || 'audio';
                var fileName = 222;//给音频文件取名字

                fileName += '.mp3';//保存成mp3格式

                // create FormData
                var formData = new FormData();
                formData.append(fileType + '-filename', fileName);
                formData.append(fileType + '-blob', blob);

                callback('Uploading ' + fileType + ' recording to server.');

                makeXMLHttpRequest('save.php', formData, function(progress) 
				{
                    if (progress !== 'upload-ended') {
                        callback(progress);
                        return;
                    }

                    //var initialURL = location.href.replace(location.href.split('/').pop(), '') + 'uploads/';
                    //callback('ended', initialURL + fileName);
					
                });
            }

            function makeXMLHttpRequest(url, data, callback) 
			{
                var request = new XMLHttpRequest();
                request.onreadystatechange = function() {
                    if (request.readyState == 4 && request.status == 200) {
                        callback('upload-ended');
                    }
                };

                request.upload.onloadstart = function() {
                    callback('Upload started...');
                };

                request.upload.onprogress = function(event) {
                    callback('Upload Progress ' + Math.round(event.loaded / event.total * 100) + "%");
                };

                request.upload.onload = function() {
                    callback('progress-about-to-end');
                };

                request.upload.onload = function() {
                    callback('progress-ended');
                };

                request.upload.onerror = function(error) {
                    callback('Failed to upload to server');
                    console.error('XMLHttpRequest failed', error);
                };

                request.upload.onabort = function(error) {
                    callback('Upload aborted.');
                    console.error('XMLHttpRequest aborted', error);
                };

                request.open('POST', url);
                request.send(data);
            }
			
        </script>

        

        

        
       
	
	
    
</body>

</html>
