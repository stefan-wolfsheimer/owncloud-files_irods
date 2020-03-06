%define _prefix         /var
%define _datadir        /var/www
%define owncloud_dir   	%{_datadir}/owncloud
%define apps_dir        %{owncloud_dir}/apps
%define apps_path       files_irods
%{!?version: %define version 0.0.6}
%{!?release: %define release 0}
%{!?branch: %define branch v%{version}}

Name:           owncloud-files_irods
Version:        %{version}
Release:        %{release}
Summary:        iRODS storage driver
License:        Apache 2
Group:          Applications/Internet
Distribution:   SURFsara
Vendor:         SURFsara
Packager:       Stefan Wolfsheimer <stefan.wolfsheimer@surfsara.nl>
BuildArch:      noarch
AutoReqProv:    no
Requires:       owncloud >= 10.0.10

%description
iRODS storage driver for OwnCloud

# %prep

%setup -q -n %{name}

%build
rm -rf %{name}-%{version}-%{release}
cp -r /host %{name}-%{version}-%{release}

%install
mkdir -p %{buildroot}%{apps_dir}

# Start!
install -dm 755 %{buildroot}%{apps_dir}/%{apps_path}

# install content
for d in $(find %{name}-%{version}-%{release} -mindepth 1 -maxdepth 1 -type d \( ! -iname ".*" ! -iname config \) ); do
     cp -a "$d" %{buildroot}%{apps_dir}/%{apps_path}
done

# Copy files in root dir
for f in $(find %{name}-%{version}-%{release} -mindepth 1 -maxdepth 1 -type f \( ! -iname ".*" ! -iname irods_environment.json \) ); do
   cp -a "$f" %{buildroot}%{apps_dir}/%{apps_path}/
   install -pm 644 "$f" %{buildroot}%{apps_dir}/%{apps_path}
done

if [ -e %{name}-%{version}-%{release}/irods_environment.json  ]
then
    install -dm 755 %{buildroot}/etc/irods
    install -pm 644 %{name}-%{version}-%{release}/irods_environment.json %{buildroot}/etc/irods
fi

# Alle rechten even goedzetten voor Apache
%files
%attr(-,apache,apache) %{apps_dir}/%{apps_path}
%attr(-,apache,apache) /etc/irods/irods_environment.json

%changelog
* Wed Feb 19 2020 Stefan Wolfsheimer <stefan.wolfsheimer@surfsara.nl>
- improved caching and attempt to fix IRD-239

* Wed Feb 05 2020 Stefan Wolfsheimer <stefan.wolfsheimer@surfsara.nl>
- fix: IRD-235

* Fri Jan 20 2020 Stefan Wolfsheimer <stefan.wolfsheimer@surfsara.nl>
- workaround for RDM-195 (connection to 4.2.7 yielded false positive exception)

* Fri Jan 10 2020 Stefan Wolfsheimer <stefan.wolfsheimer@surfsara.nl>
- Add irods_enviornment.json file

* Mon Oct 07 2019 Stefan Wolfsheimer <stefan.wolfsheimer@surfsara.nl> v0.0.1-1
- Package irods storage driver

