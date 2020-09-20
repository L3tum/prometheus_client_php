$image = "composer:latest"
$pwd = $PSScriptRoot.Replace("\", "/")
$linux_pwd = "/host_mnt/" + $pwd.Replace(":", "").ToLower()

docker run -it --rm --volume /var/run/docker.sock:/var/run/docker.sock --volume ${pwd}:${linux_pwd} -w ${linux_pwd} --net host --entrypoint composer $image $args
