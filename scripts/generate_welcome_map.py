"""Build the East Java SVG paths used by the welcome page.

Input: province and regency GeoJSON from AlfianAliM/Indonesia-GeoJSON (MIT).
Run: python scripts/generate_welcome_map.py path/to/provinsi.geojson path/to/kab_kota.geojson
"""

import json
import sys
from pathlib import Path


WEST, NORTH, SCALE = 110.8, -5.4, 155


def area(ring):
    return abs(sum(
        ring[index][0] * ring[index + 1][1]
        - ring[index + 1][0] * ring[index][1]
        for index in range(len(ring) - 1)
    )) / 2


def project(point):
    longitude, latitude = point
    return round((longitude - WEST) * SCALE, 1), round((NORTH - latitude) * SCALE, 1)


def polygons(geometry):
    if geometry["type"] == "Polygon":
        return [geometry["coordinates"]]
    return geometry["coordinates"]


def svg_path(rings):
    commands = []
    for ring in rings:
        points = [project(point) for point in ring]
        commands.append("M" + " L".join(f"{x:g} {y:g}" for x, y in points) + " Z")
    return " ".join(commands)


def main():
    if len(sys.argv) != 3:
        raise SystemExit("Usage: python scripts/generate_welcome_map.py provinsi.geojson kab_kota.geojson")

    province_data = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
    district_data = json.loads(Path(sys.argv[2]).read_text(encoding="utf-8"))
    province = next(feature for feature in province_data["features"] if feature["properties"]["code"] == "35")
    province_rings = [
        polygon[0]
        for polygon in polygons(province["geometry"])
        if area(polygon[0]) > 0.001
    ]
    districts = sorted(
        (feature for feature in district_data["features"] if feature["properties"]["code"].startswith("35.")),
        key=lambda feature: feature["properties"]["code"],
    )
    district_paths = [
        {"name": feature["properties"]["name"], "path": svg_path(
            polygon[0] for polygon in polygons(feature["geometry"])
            if area(polygon[0]) > 0.00005
        )}
        for feature in districts
    ]

    path = svg_path(province_rings)
    output = Path("resources/js/Pages/jawaTimurGeometry.js")
    output.write_text(
        "// Derived from East Java province and regency GeoJSON; source and license in implementation.md.\n"
        f"export const eastJavaProjection = {{ west: {WEST:g}, north: {NORTH:g}, scale: {SCALE:g}, width: 806, height: 543 }};\n"
        f"export const eastJavaPath = {json.dumps(path)};\n"
        f"export const eastJavaDistrictPaths = {json.dumps(district_paths, ensure_ascii=False, separators=(',', ':'))};\n",
        encoding="utf-8",
    )
    print(f"Wrote {output} with {len(province_rings)} province polygons and {len(district_paths)} regencies/cities")


if __name__ == "__main__":
    main()
