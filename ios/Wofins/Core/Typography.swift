import SwiftUI

enum WofinsFont {
    static func poppins(size: CGFloat, weight: Font.Weight = .regular) -> Font {
        .custom(name(for: weight), size: size)
    }

    static func poppins(_ style: Font.TextStyle, weight: Font.Weight = .regular) -> Font {
        .custom(name(for: weight), size: size(for: style), relativeTo: style)
    }

    private static func name(for weight: Font.Weight) -> String {
        switch weight {
        case .ultraLight, .thin, .light:
            return "Poppins-Light"
        case .medium:
            return "Poppins-Medium"
        case .semibold:
            return "Poppins-SemiBold"
        case .bold, .heavy, .black:
            return "Poppins-Bold"
        default:
            return "Poppins-Regular"
        }
    }

    private static func size(for style: Font.TextStyle) -> CGFloat {
        switch style {
        case .largeTitle: return 34
        case .title: return 28
        case .title2: return 22
        case .title3: return 20
        case .headline: return 17
        case .body: return 17
        case .callout: return 16
        case .subheadline: return 15
        case .footnote: return 13
        case .caption: return 12
        case .caption2: return 11
        @unknown default: return 17
        }
    }
}

extension Font {
    static func poppins(size: CGFloat, weight: Font.Weight = .regular) -> Font {
        WofinsFont.poppins(size: size, weight: weight)
    }

    static func poppins(_ style: Font.TextStyle, weight: Font.Weight = .regular) -> Font {
        WofinsFont.poppins(style, weight: weight)
    }
}
