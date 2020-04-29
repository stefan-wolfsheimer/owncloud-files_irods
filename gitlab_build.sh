#!/bin/bash
set -x
set -e

NAME=owncloud-files_irods
SPEC=${NAME}.spec
OC_VERSION=oc-10.3

BUILD_IMG=git.ia.surfsara.nl:5050/data-management-services/owncloud-app-builder/owncloud-rpm-builder
CONTAINER_NAME=owncloud-files-irods-builder

echo $OWNCLOUD_APP_BUILDER_PASSWORD | docker login -u $OWNCLOUD_APP_BUILDER_USER --password-stdin  git.ia.surfsara.nl:5050/data-management-services/owncloud-app-builder/owncloud-rpm-builder


if [ -z "$CI_PIPELINE_ID" ]
then
    CI_PIPELINE_ID=1
fi

if [ -z "$CI_COMMIT_REF_NAME" ]
then
    CI_COMMIT_REF_NAME=master
fi

if [ -z "$CI_COMMIT_TAG" ]
then
     VERSION=${CI_PIPELINE_ID}
     RELEASE=0
     BRANCH=${CI_COMMIT_REF_NAME}
     REPO=DMS-RPM-Testing
     REPOS=(DMS-RPM-Testing)
else
     # release
     VERSION=${CI_COMMIT_TAG}
     RELEASE=0
     BRANCH=release
     REPOS=(DMS-RPM-Testing DMS-RPM-Production)
fi

RPM=${NAME}-${VERSION}-${RELEASE}.noarch.rpm
TARGET=Centos/7/$OC_VERSION/${BRANCH}/noarch/Packages/${RPM}

set +e
docker rm -f $CONTAINER_NAME
set -e


docker run -v $( pwd ):/host --name $CONTAINER_NAME $BUILD_IMG \
       rpmbuild -ba /host/${SPEC} --target noarch --define "version ${VERSION}" --define "release ${RELEASE}" --define "branch $CI_COMMIT_REF_NAME"
docker cp $CONTAINER_NAME:/home/builder/rpm/noarch/${RPM} .

set +x
ret=0
for REPO in ${REPOS[@]}; do
    if [ -z "$ARTIE_KEY" ]
    then
        echo "no ARTIE_KEY defined: not published"
        echo curl -H "X-JFrog-Art-Api:$ARTIE_KEY" -XPUT https://artie.ia.surfsara.nl/artifactory/${REPO}/${TARGET} -T ${RPM}
        ret=1
    else
        curl -H "X-JFrog-Art-Api:$ARTIE_KEY" -XPUT https://artie.ia.surfsara.nl/artifactory/${REPO}/${TARGET} -T ${RPM}
    fi
done


exit $ret
