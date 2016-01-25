#!/bin/bash
#
# Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
# Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
#
include=(adminpages classes css images includes js languages e20r-blur-protected-content.php readme.txt)
exclude=(vendor *.yml *.phar composer.*)
build=(classes/plugin-updates/vendor/*.php)
short_name="e20r-blur-protected-content"
plugin_path="${short_name}"
readme_path="../build_readmes/"
changelog_source=${readme_path}current.txt
meta_log_source=${readme_path}existing_json.txt
readme_source=${readme_path}existing_readme.txt
json_template="metadata.json.template"
readme_template="README.txt.template"
readme_txt="README.txt"
readme_json="metadata.json"
metadata="../metadata.json"
version=$(egrep "^Version:" ../${short_name}.php | awk '{print $2}')
src_path="../"
dst_path="../build/${plugin_path}"
kit_path="../build/kits"
kit_name="${kit_path}/${short_name}-${version}"

echo "Building kit for version ${version}"

mkdir -p ${kit_path}
mkdir -p ${dst_path}

if [[ -f  ${kit_name} ]]
then
    echo "Kit is already present. Cleaning up"
    rm -rf ${dst_path}
    rm -f ${kit_name}
fi


for p in ${include[@]}; do
	cp -R ${src_path}${p} ${dst_path}
done

for e in ${exclude[@]}; do
    find ${dst_path} -name ${e} -exec rm -rf {} \;
done

mkdir -p ${dst_path}/classes/plugin-updates/vendor/
for b in ${build[@]}; do
    cp ${src_path}${b} ${dst_path}/classes/plugin-updates/vendor/
done

cd ${dst_path}/..
zip -r ${kit_name}.zip ${plugin_path}
ssh siteground-e20r "cd ./www/protected-content/ ; mkdir -p \"${short_name}\""
scp ${kit_name}.zip siteground-e20r:./www/protected-content/e20r-blur-protected-content/
scp ${metadata} siteground-e20r:./www/protected-content/e20r-blur-protected-content/
ssh siteground-e20r "cd ./www/protected-content/ ; ln -sf \"${short_name}\"/\"${short_name}\"-\"${version}\".zip \"${short_name}\".zip"
rm -rf ${dst_path}
