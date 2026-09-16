import SwiftUI

enum AppColorTheme: String, CaseIterable, Identifiable {
    case wofins
    case hastana

    static let storageKey = "wofins.colorTheme"

    var id: String { rawValue }

    var name: String {
        switch self {
        case .wofins: return "WOFINS Biru"
        case .hastana: return "Hastana"
        }
    }

    var description: String {
        switch self {
        case .wofins: return "Tema biru WOFINS saat ini"
        case .hastana: return "Perpaduan merah, hitam, dan putih"
        }
    }

    var previewColors: [Color] {
        switch self {
        case .wofins:
            return [
                Color(red: 0.00, green: 0.27, blue: 0.50),
                Color(red: 1.00, green: 0.73, blue: 0.00),
                .white,
            ]
        case .hastana:
            return [
                Color(red: 0.70, green: 0.07, blue: 0.11),
                Color(red: 0.07, green: 0.07, blue: 0.08),
                .white,
            ]
        }
    }

    static var selected: AppColorTheme {
        AppColorTheme(
            rawValue: UserDefaults.standard.string(forKey: storageKey) ?? ""
        ) ?? .hastana
    }
}
